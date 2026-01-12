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

	const COOKIE_NAME = 'preview_ai_sid';

	/**
	 * Set session cookie on page load (call from wp_footer or similar).
	 * Must run BEFORE any AJAX to ensure cookie exists.
	 */
	public static function maybe_set_cookie() {
		if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) || headers_sent() ) {
			return;
		}
		$sid = md5( uniqid( 'pai_', true ) );
		setcookie( self::COOKIE_NAME, $sid, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), false );
	}

	/**
	 * Get session ID from our cookie.
	 *
	 * @return string|null
	 */
	private static function get_session_id() {
		$sid = isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : null;
		PREVIEW_AI_Logger::debug( 'get_session_id', array( 'sid' => $sid ) );
		return $sid;
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
		if ( null === $session_id ) {
			$session_id = self::get_session_id();
		}

		$data = array(
			'event_type'   => $event_type,
			'user_id'      => $user_id > 0 ? $user_id : null,
			'session_id'   => $session_id,
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
	 * Save session_id to order for attribution.
	 *
	 * @param int|\WC_Order $order_or_id Order ID (classic) or Order object (blocks).
	 */
	public static function save_to_order( $order_or_id ) {
		$order = $order_or_id instanceof \WC_Order ? $order_or_id : wc_get_order( $order_or_id );
		if ( ! $order ) {
			return;
		}

		$session_id = self::get_session_id();
		if ( $session_id ) {
			$order->update_meta_data( '_preview_ai_session_id', $session_id );
			$order->save();
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

		// Build query to find previews from this user.
		$table = self::get_table_name();
		$since = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

		$where_clauses = array( "event_type = 'preview'", "created_at >= %s" );
		$params        = array( $since );

		$user_where = array();
		if ( $user_id > 0 ) {
			$user_where[] = $wpdb->prepare( 'user_id = %d', $user_id );
		}
		if ( ! empty( $session_id ) ) {
			$user_where[] = $wpdb->prepare( 'session_id = %s', $session_id );
		}

		if ( empty( $user_where ) ) {
			return;
		}

		$where_clauses[] = '(' . implode( ' OR ', $user_where ) . ')';
		$where_str       = implode( ' AND ', $where_clauses );

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$previewed = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT product_id, variation_id FROM $table WHERE $where_str",
				...$params
			),
			ARRAY_A
		);


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
			PREVIEW_AI_Logger::debug( 'no-converted-items', array( 'converted_items' => $converted_items ) );
			return;
		}

		// Record conversion events.
		$order_total = (float) $order->get_total();
		foreach ( $converted_items as $item ) {
			self::record_event(
				'conversion',
				$item['product_id'],
				$item['variation_id'],
				$order_id,
				$order_total,
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
	 * Get aggregated stats.
	 *
	 * @return array
	 */
	public static function get_stats() {
		$detailed = self::get_detailed_stats( 'all' );
		return array(
			'previews'    => $detailed['previews'],
			'conversions' => $detailed['conversions'],
			'refunds'     => $detailed['refunds'],
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
		// We use a subquery to get the total of each unique order influenced.
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$revenue_data = $wpdb->get_row(
			"SELECT 
				SUM(CASE WHEN event_type = 'conversion' THEN order_total ELSE 0 END) as influenced_revenue,
				SUM(CASE WHEN event_type = 'refund' THEN order_total ELSE 0 END) as refunded_revenue,
				AVG(CASE WHEN event_type = 'conversion' THEN order_total END) as avg_order_value
			FROM (
				SELECT DISTINCT order_id, order_total, event_type
				FROM $table 
				WHERE event_type IN ('conversion', 'refund') AND order_id IS NOT NULL $date_filter
			) as unique_orders",
			ARRAY_A
		);

		$influenced_revenue = (float) ( $revenue_data['influenced_revenue'] ?? 0 );
		$refunded_revenue   = (float) ( $revenue_data['refunded_revenue'] ?? 0 );
		$avg_order_value    = (float) ( $revenue_data['avg_order_value'] ?? 0 );
		$net_revenue        = $influenced_revenue - $refunded_revenue;

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
			'refunded_revenue'     => $refunded_revenue,
			'net_revenue'          => $net_revenue,

			// Legacy (for backward compatibility).
			'previews'             => $users_tried,
			'conversions'          => $orders_influenced,
			'refunds'              => $orders_refunded,
			'conversion_rate'      => $user_conversion_rate,
			'revenue'              => $influenced_revenue,
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
