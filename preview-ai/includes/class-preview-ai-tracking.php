<?php
/**
 * Conversion tracking with user attribution.
 *
 * Simple approach: the events table is the single source of truth.
 * - track_preview() saves to table
 * - save_to_order() saves session_id for guest attribution
 * - track_completed() queries the table directly
 *
 * @package Preview_Ai
 */

class PREVIEW_AI_Tracking {

	const OPTION_STATS = 'preview_ai_stats';
	const DB_VERSION = '1.0';
	const DB_VERSION_OPTION = 'preview_ai_tracking_db_version';

	/**
	 * Get table name.
	 *
	 * @return string
	 */
	public static function get_table_name() {
		global $wpdb;
		return $wpdb->prefix . 'preview_ai_events';
	}

	/**
	 * Create tracking table.
	 */
	public static function create_table() {
		global $wpdb;

		$table_name      = self::get_table_name();
		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE $table_name (
			id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
			event_type varchar(20) NOT NULL,
			user_id bigint(20) UNSIGNED DEFAULT NULL,
			session_id varchar(32) DEFAULT NULL,
			product_id bigint(20) UNSIGNED NOT NULL,
			variation_id bigint(20) UNSIGNED DEFAULT NULL,
			order_id bigint(20) UNSIGNED DEFAULT NULL,
			order_total decimal(10,2) DEFAULT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			KEY event_type (event_type),
			KEY user_id (user_id),
			KEY session_id (session_id),
			KEY product_id (product_id),
			KEY order_id (order_id),
			KEY created_at (created_at)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );

		update_option( self::DB_VERSION_OPTION, self::DB_VERSION );
	}

	/**
	 * Maybe create/update table.
	 */
	public static function maybe_create_table() {
		if ( get_option( self::DB_VERSION_OPTION ) !== self::DB_VERSION ) {
			self::create_table();
		}
	}

	/**
	 * Get session ID for guest users.
	 *
	 * @return string|null
	 */
	private static function get_session_id() {
		if ( function_exists( 'WC' ) && WC()->session ) {
			return substr( md5( WC()->session->get_customer_id() ), 0, 32 );
		}
		return null;
	}

	/**
	 * Record an event.
	 *
	 * @param string     $event_type   Event type: preview, conversion, refund.
	 * @param int        $product_id   Product ID.
	 * @param int|null   $variation_id Variation ID.
	 * @param int|null   $order_id     Order ID.
	 * @param float|null $order_total  Order total.
	 * @param int|null   $user_id      User ID (optional, defaults to current user).
	 * @param string|null $session_id  Session ID (optional, for guests).
	 * @return int|false Insert ID or false.
	 */
	private static function record_event( $event_type, $product_id, $variation_id = null, $order_id = null, $order_total = null, $user_id = null, $session_id = null ) {
		global $wpdb;

		// Use provided user_id or get current user.
		if ( null === $user_id ) {
			$user_id = get_current_user_id();
		}

		// Use provided session_id or get current session.
		if ( null === $session_id && $user_id <= 0 ) {
			$session_id = self::get_session_id();
		}

		$data = array(
			'event_type'   => $event_type,
			'user_id'      => $user_id > 0 ? $user_id : null,
			'session_id'   => $user_id > 0 ? null : $session_id,
			'product_id'   => absint( $product_id ),
			'variation_id' => $variation_id ? absint( $variation_id ) : null,
			'order_id'     => $order_id ? absint( $order_id ) : null,
			'order_total'  => $order_total,
			'created_at'   => current_time( 'mysql' ),
		);

		$result = $wpdb->insert( self::get_table_name(), $data );

		if ( false === $result ) {
			return false;
		}

		// Update aggregated stats.
		$stat_key = 'preview' === $event_type ? 'previews' : ( 'conversion' === $event_type ? 'conversions' : 'refunds' );
		self::increment_stat( $stat_key );

		return $wpdb->insert_id;
	}

	/**
	 * Track preview generation.
	 *
	 * @param int      $product_id   Product ID.
	 * @param int|null $variation_id Variation ID.
	 */
	public static function track_preview( $product_id, $variation_id = null ) {
		self::record_event( 'preview', $product_id, $variation_id );
	}

	/**
	 * Save session_id to order for guest attribution.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save_to_order( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Only save session_id for guests.
		if ( $order->get_user_id() <= 0 ) {
			$session_id = self::get_session_id();
			if ( $session_id ) {
				$order->update_meta_data( '_preview_ai_session_id', $session_id );
				$order->save();
			}
		}
	}

	/**
	 * Track conversion when order is completed.
	 * Queries the events table directly to find previewed products.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function track_completed( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Already tracked.
		if ( $order->get_meta( '_preview_ai_converted' ) ) {
			return;
		}

		// Get user identifier.
		$user_id    = $order->get_user_id();
		$session_id = $order->get_meta( '_preview_ai_session_id' );

		// Need at least one identifier.
		if ( $user_id <= 0 && empty( $session_id ) ) {
			return;
		}

		// Get product IDs from order.
		$order_products = array();
		foreach ( $order->get_items() as $item ) {
			$product_id   = $item->get_product_id();
			$variation_id = $item->get_variation_id();

			$order_products[] = array(
				'product_id'   => $product_id,
				'variation_id' => $variation_id ? $variation_id : null,
				'total'        => $item->get_total(),
			);
		}

		if ( empty( $order_products ) ) {
			return;
		}

		// Build query to find previews from this user in last 7 days.
		$table = self::get_table_name();
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		if ( $user_id > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$previewed = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT product_id, variation_id FROM $table 
					WHERE user_id = %d AND event_type = 'preview' AND created_at >= %s",
					$user_id,
					$since
				),
				ARRAY_A
			);
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$previewed = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT DISTINCT product_id, variation_id FROM $table 
					WHERE session_id = %s AND event_type = 'preview' AND created_at >= %s",
					$session_id,
					$since
				),
				ARRAY_A
			);
		}

		if ( empty( $previewed ) ) {
			return;
		}

		// Index previewed products for fast lookup.
		$previewed_map = array();
		foreach ( $previewed as $p ) {
			$previewed_map[ (int) $p['product_id'] ] = true;
		}

		// Find matches.
		$converted_items = array();
		foreach ( $order_products as $item ) {
			if ( isset( $previewed_map[ $item['product_id'] ] ) ) {
				$converted_items[] = $item;
			}
		}

		if ( empty( $converted_items ) ) {
			return;
		}

		// Record conversion events.
		foreach ( $converted_items as $item ) {
			self::record_event(
				'conversion',
				$item['product_id'],
				$item['variation_id'],
				$order_id,
				$item['total'],
				$user_id > 0 ? $user_id : null,
				$user_id <= 0 ? $session_id : null
			);
		}

		// Mark order as converted.
		$order->update_meta_data( '_preview_ai_converted', true );
		$order->save();
	}

	/**
	 * Track refund.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function track_refunded( $order_id ) {
		global $wpdb;

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		if ( ! $order->get_meta( '_preview_ai_converted' ) ) {
			return;
		}

		if ( $order->get_meta( '_preview_ai_refunded' ) ) {
			return;
		}

		// Find conversion events for this order and mark as refunded.
		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$conversions = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT product_id, variation_id, order_total FROM $table 
				WHERE order_id = %d AND event_type = 'conversion'",
				$order_id
			),
			ARRAY_A
		);

		$user_id    = $order->get_user_id();
		$session_id = $order->get_meta( '_preview_ai_session_id' );

		foreach ( $conversions as $conv ) {
			self::record_event(
				'refund',
				$conv['product_id'],
				$conv['variation_id'],
				$order_id,
				$conv['order_total'],
				$user_id > 0 ? $user_id : null,
				$user_id <= 0 ? $session_id : null
			);
		}

		$order->update_meta_data( '_preview_ai_refunded', true );
		$order->save();
	}

	/**
	 * Increment aggregated stat.
	 *
	 * @param string $key Stat key.
	 */
	private static function increment_stat( $key ) {
		$stats         = get_option( self::OPTION_STATS, array() );
		$stats[ $key ] = isset( $stats[ $key ] ) ? $stats[ $key ] + 1 : 1;
		update_option( self::OPTION_STATS, $stats, false );
	}

	/**
	 * Get aggregated stats.
	 *
	 * @return array
	 */
	public static function get_stats() {
		return wp_parse_args(
			get_option( self::OPTION_STATS, array() ),
			array(
				'previews'    => 0,
				'conversions' => 0,
				'refunds'     => 0,
			)
		);
	}

	/**
	 * Get detailed statistics with merchant-friendly metrics.
	 *
	 * @param string $period Period: today, 7days, 30days, all.
	 * @return array
	 */
	public static function get_detailed_stats( $period = '30days' ) {
		global $wpdb;

		$table = self::get_table_name();

		// Date filter.
		$date_filter = '';
		switch ( $period ) {
			case 'today':
				$date_filter = $wpdb->prepare( 'AND created_at >= %s', gmdate( 'Y-m-d 00:00:00' ) );
				break;
			case '7days':
				$date_filter = $wpdb->prepare( 'AND created_at >= %s', gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) ) );
				break;
			case '30days':
				$date_filter = $wpdb->prepare( 'AND created_at >= %s', gmdate( 'Y-m-d 00:00:00', strtotime( '-30 days' ) ) );
				break;
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$counts = $wpdb->get_row(
			"SELECT 
				COUNT(DISTINCT CASE WHEN event_type = 'preview' THEN COALESCE(user_id, session_id) END) as users_tried,
				COUNT(DISTINCT CASE WHEN event_type = 'conversion' THEN order_id END) as orders_influenced,
				COUNT(DISTINCT CASE WHEN event_type = 'conversion' THEN COALESCE(user_id, session_id) END) as users_converted,
				COUNT(DISTINCT CASE WHEN event_type = 'refund' THEN order_id END) as orders_refunded
			FROM $table
			WHERE 1=1 $date_filter",
			ARRAY_A
		);

		$users_tried       = (int) ( $counts['users_tried'] ?? 0 );
		$orders_influenced = (int) ( $counts['orders_influenced'] ?? 0 );
		$users_converted   = (int) ( $counts['users_converted'] ?? 0 );
		$orders_refunded   = (int) ( $counts['orders_refunded'] ?? 0 );

		// Get total revenue from influenced orders (full order value).
		$influenced_revenue = 0;
		$avg_order_value    = 0;
		if ( $orders_influenced > 0 ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$order_ids = $wpdb->get_col(
				"SELECT DISTINCT order_id FROM $table 
				WHERE event_type = 'conversion' AND order_id IS NOT NULL $date_filter"
			);

			if ( ! empty( $order_ids ) ) {
				foreach ( $order_ids as $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						$influenced_revenue += (float) $order->get_total();
					}
				}
				$avg_order_value = $influenced_revenue / count( $order_ids );
			}
		}

		// User conversion rate (users who tried AND bought).
		$user_conversion_rate = $users_tried > 0 ? round( ( $users_converted / $users_tried ) * 100, 1 ) : 0;

		return array(
			// Primary metrics.
			'users_tried'          => $users_tried,
			'orders_influenced'    => $orders_influenced,
			'user_conversion_rate' => $user_conversion_rate,
			'influenced_revenue'   => $influenced_revenue,

			// Secondary metrics.
			'avg_order_value'      => round( $avg_order_value, 2 ),
			'orders_refunded'      => $orders_refunded,

			// Legacy (for backward compatibility).
			'previews'             => $users_tried,
			'conversions'          => $orders_influenced,
			'refunds'              => $orders_refunded,
			'conversion_rate'      => $user_conversion_rate,
			'revenue'              => $influenced_revenue,
			'net_revenue'          => $influenced_revenue,
			'unique_users'         => $users_tried,
		);
	}

	/**
	 * Get top converting products.
	 *
	 * @param int $limit Number of products.
	 * @return array
	 */
	public static function get_top_products( $limit = 5 ) {
		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT 
					product_id,
					SUM(CASE WHEN event_type = 'preview' THEN 1 ELSE 0 END) as previews,
					SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions,
					SUM(CASE WHEN event_type = 'conversion' THEN order_total ELSE 0 END) as revenue
				FROM $table
				WHERE product_id > 0
				GROUP BY product_id
				HAVING conversions > 0
				ORDER BY conversions DESC, revenue DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		foreach ( $results as &$row ) {
			$product             = wc_get_product( $row['product_id'] );
			$row['product_name'] = $product ? $product->get_name() : __( 'Deleted product', 'preview-ai' );
			$row['conversion_rate'] = $row['previews'] > 0 ? round( ( $row['conversions'] / $row['previews'] ) * 100, 1 ) : 0;
		}

		return $results;
	}

	/**
	 * Get recent conversions.
	 *
	 * @param int $limit Number of conversions.
	 * @return array
	 */
	public static function get_recent_conversions( $limit = 10 ) {
		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT e.*, u.display_name, u.user_email
				FROM $table e
				LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
				WHERE e.event_type = 'conversion'
				ORDER BY e.created_at DESC
				LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		foreach ( $results as &$row ) {
			$product             = wc_get_product( $row['product_id'] );
			$row['product_name'] = $product ? $product->get_name() : __( 'Deleted product', 'preview-ai' );
			$row['customer_name'] = $row['display_name'] ? $row['display_name'] : __( 'Guest', 'preview-ai' );
		}

		return $results;
	}

	/**
	 * Get user stats by user ID.
	 *
	 * @param int $user_id User ID.
	 * @return array
	 */
	public static function get_user_stats( $user_id ) {
		global $wpdb;

		$table = self::get_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$stats = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT 
					SUM(CASE WHEN event_type = 'preview' THEN 1 ELSE 0 END) as previews,
					SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions,
					SUM(CASE WHEN event_type = 'conversion' THEN order_total ELSE 0 END) as total_spent
				FROM $table
				WHERE user_id = %d",
				$user_id
			),
			ARRAY_A
		);

		return array(
			'previews'    => (int) ( $stats['previews'] ?? 0 ),
			'conversions' => (int) ( $stats['conversions'] ?? 0 ),
			'total_spent' => (float) ( $stats['total_spent'] ?? 0 ),
		);
	}
}
