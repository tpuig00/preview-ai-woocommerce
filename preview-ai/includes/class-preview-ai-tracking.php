<?php
/**
 * Conversion tracking with user attribution.
 *
 * @package Preview_Ai
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class PREVIEW_AI_Tracking {

    const DB_VERSION        = '1.0';
    const DB_VERSION_OPTION = 'preview_ai_tracking_db_version';
    const CACHE_GROUP       = 'preview_ai_tracking';

    /**
     * Clear all tracking caches.
     */
    private static function clear_cache() {
        wp_cache_delete( 'detailed_stats_today', self::CACHE_GROUP );
        wp_cache_delete( 'detailed_stats_7days', self::CACHE_GROUP );
        wp_cache_delete( 'detailed_stats_30days', self::CACHE_GROUP );
        wp_cache_delete( 'detailed_stats_all', self::CACHE_GROUP );

        foreach ( array( 5, 10 ) as $limit ) {
            wp_cache_delete( 'top_products_' . $limit, self::CACHE_GROUP );
            wp_cache_delete( 'recent_conversions_' . $limit, self::CACHE_GROUP );
        }
    }

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
            PRIMARY KEY  (id),
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
     * Set session cookie.
     */
    public static function maybe_set_cookie() {
        if ( isset( $_COOKIE[ self::COOKIE_NAME ] ) || headers_sent() ) {
            return;
        }
        $sid = md5( uniqid( 'pai_', true ) );
        // phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.cookies_setcookie
        setcookie( self::COOKIE_NAME, $sid, time() + ( 30 * DAY_IN_SECONDS ), '/', '', is_ssl(), false );
    }

    /**
     * Get session ID.
     *
     * @return string|null
     */
    private static function get_session_id() {
        return isset( $_COOKIE[ self::COOKIE_NAME ] ) ? sanitize_text_field( wp_unslash( $_COOKIE[ self::COOKIE_NAME ] ) ) : null;
    }

    /**
     * Record an event.
     *
     * @param string      $event_type   Event type.
     * @param int         $product_id   Product ID.
     * @param int|null    $variation_id Variation ID.
     * @param int|null    $order_id     Order ID.
     * @param float|null  $order_total  Order total.
     * @param int|null    $user_id      User ID.
     * @param string|null $session_id   Session ID.
     * @return int|false Insert ID or false.
     */
    private static function record_event( $event_type, $product_id, $variation_id = null, $order_id = null, $order_total = null, $user_id = null, $session_id = null ) {
        global $wpdb;

        if ( null === $user_id ) {
            $user_id = get_current_user_id();
        }

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

        $format = array( '%s', '%d', '%s', '%d', '%d', '%d', '%f', '%s' );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
        $result = $wpdb->insert( self::get_table_name(), $data, $format );

        if ( false === $result ) {
            return false;
        }

        self::clear_cache();

        return $wpdb->insert_id;
    }

    /**
     * Track preview.
     *
     * @param int      $product_id   Product ID.
     * @param int|null $variation_id Variation ID.
     */
    public static function track_preview( $product_id, $variation_id = null ) {
        self::record_event( 'preview', $product_id, $variation_id );
    }

    /**
     * Save session to order.
     *
     * @param int|\WC_Order $order_or_id Order.
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
     * Track completed order.
     *
     * @param int $order_id Order ID.
     */
    public static function track_completed( $order_id ) {
        global $wpdb;

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        if ( $order->get_meta( '_preview_ai_converted' ) ) {
            return;
        }

        $user_id    = $order->get_user_id();
        $session_id = $order->get_meta( '_preview_ai_session_id' );

        if ( $user_id <= 0 && empty( $session_id ) ) {
            return;
        }

        // Collect order items.
        $order_products = array();
        foreach ( $order->get_items() as $item ) {
            $order_products[] = array(
                'product_id'   => $item->get_product_id(),
                'variation_id' => $item->get_variation_id() ? $item->get_variation_id() : null,
            );
        }

        if ( empty( $order_products ) ) {
            return;
        }

        $table = self::get_table_name();
        $since = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );

        // Build query parts manually to satisfy scanner.
        $query_args = array( 'preview', $since );
        $user_logic = array();

        if ( $user_id > 0 ) {
            $user_logic[] = 'user_id = %d';
            $query_args[] = $user_id;
        }
        if ( ! empty( $session_id ) ) {
            $user_logic[] = 'session_id = %s';
            $query_args[] = $session_id;
        }

        if ( empty( $user_logic ) ) {
            return;
        }

        $logic_str = implode( ' OR ', $user_logic );
        
        // Construct the full SQL string before prepare.
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $sql = "SELECT DISTINCT product_id, variation_id 
                FROM {$table} 
                WHERE event_type = %s 
                AND created_at >= %s 
                AND ( {$logic_str} )";
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $previewed = $wpdb->get_results( $wpdb->prepare( $sql, $query_args ), ARRAY_A );

        if ( empty( $previewed ) ) {
            return;
        }

        // Map logic...
        $previewed_map = array();
        foreach ( $previewed as $p ) {
            $previewed_map[ (int) $p['product_id'] ] = true;
        }

        $converted_items = array();
        foreach ( $order_products as $item ) {
            if ( isset( $previewed_map[ $item['product_id'] ] ) ) {
                $converted_items[] = $item;
            }
        }

        if ( empty( $converted_items ) ) {
            return;
        }

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
        if ( ! $order || ! $order->get_meta( '_preview_ai_converted' ) || $order->get_meta( '_preview_ai_refunded' ) ) {
            return;
        }

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $conversions = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT product_id, variation_id, order_total FROM {$table} WHERE order_id = %d AND event_type = %s",
                $order_id,
                'conversion'
            ),
            ARRAY_A
        );

        if ( ! empty( $conversions ) ) {
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
     * Get detailed statistics.
     *
     * @param string $period Period.
     * @return array
     */
    public static function get_detailed_stats( $period = '30days' ) {
        global $wpdb;

        $cache_key = 'detailed_stats_' . $period;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            return $cached;
        }

        $table = self::get_table_name();

        // Prepare date filter arguments.
        $query_args = array();
        $date_sql   = '';

        switch ( $period ) {
            case 'today':
                $date_sql     = 'AND created_at >= %s';
                $query_args[] = gmdate( 'Y-m-d 00:00:00' );
                break;
            case '7days':
                $date_sql     = 'AND created_at >= %s';
                $query_args[] = gmdate( 'Y-m-d 00:00:00', strtotime( '-7 days' ) );
                break;
            case '30days':
                $date_sql     = 'AND created_at >= %s';
                $query_args[] = gmdate( 'Y-m-d 00:00:00', strtotime( '-30 days' ) );
                break;
        }

        // QUERY 1: COUNTS
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $count_sql = "SELECT 
            COUNT(DISTINCT CASE WHEN event_type = 'preview' THEN COALESCE(user_id, session_id) END) as users_tried,
            COUNT(DISTINCT CASE WHEN event_type = 'conversion' THEN order_id END) as orders_influenced,
            COUNT(DISTINCT CASE WHEN event_type = 'conversion' THEN COALESCE(user_id, session_id) END) as users_converted,
            COUNT(DISTINCT CASE WHEN event_type = 'refund' THEN order_id END) as orders_refunded
        FROM {$table}
        WHERE 1=1 {$date_sql}";
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $counts = $wpdb->get_row( $wpdb->prepare( $count_sql, $query_args ), ARRAY_A );

        // Extract counts safely.
        $users_tried       = (int) ( $counts['users_tried'] ?? 0 );
        $orders_influenced = (int) ( $counts['orders_influenced'] ?? 0 );
        $users_converted   = (int) ( $counts['users_converted'] ?? 0 );
        $orders_refunded   = (int) ( $counts['orders_refunded'] ?? 0 );

        // QUERY 2: REVENUE
        // Need to merge arguments for the revenue query (event types + date).
        $rev_args = array_merge( array( 'conversion', 'refund' ), $query_args );

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $rev_sql = "SELECT 
            SUM(CASE WHEN event_type = 'conversion' THEN order_total ELSE 0 END) as influenced_revenue,
            SUM(CASE WHEN event_type = 'refund' THEN order_total ELSE 0 END) as refunded_revenue,
            AVG(CASE WHEN event_type = 'conversion' THEN order_total END) as avg_order_value
        FROM (
            SELECT DISTINCT order_id, order_total, event_type
            FROM {$table} 
            WHERE event_type IN (%s, %s) AND order_id IS NOT NULL {$date_sql}
        ) as unique_orders";
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
        $revenue_data = $wpdb->get_row( $wpdb->prepare( $rev_sql, $rev_args ), ARRAY_A );

        $influenced_revenue = (float) ( $revenue_data['influenced_revenue'] ?? 0 );
        $refunded_revenue   = (float) ( $revenue_data['refunded_revenue'] ?? 0 );
        $avg_order_value    = (float) ( $revenue_data['avg_order_value'] ?? 0 );

        $result = array(
            'users_tried'          => $users_tried,
            'orders_influenced'    => $orders_influenced,
            'user_conversion_rate' => $users_tried > 0 ? round( ( $users_converted / $users_tried ) * 100, 1 ) : 0,
            'influenced_revenue'   => $influenced_revenue,
            'avg_order_value'      => round( $avg_order_value, 2 ),
            'orders_refunded'      => $orders_refunded,
            'refunded_revenue'     => $refunded_revenue,
            'net_revenue'          => $influenced_revenue - $refunded_revenue,
            // Legacy/Compat keys
            'previews'             => $users_tried,
            'conversions'          => $orders_influenced,
            'refunds'              => $orders_refunded,
        );

        wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $result;
    }

    /**
     * Get top products.
     *
     * @param int $limit Limit.
     * @return array
     */
    public static function get_top_products( $limit = 5 ) {
        global $wpdb;

        $limit     = absint( $limit );
        $cache_key = 'top_products_' . $limit;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            return $cached;
        }

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT 
                    product_id,
                    SUM(CASE WHEN event_type = 'preview' THEN 1 ELSE 0 END) as previews,
                    SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions,
                    SUM(CASE WHEN event_type = 'conversion' THEN order_total ELSE 0 END) as revenue
                FROM {$table}
                WHERE product_id > 0
                GROUP BY product_id
                HAVING conversions > 0
                ORDER BY conversions DESC, revenue DESC
                LIMIT %d",
                $limit
            ),
            ARRAY_A
        );

        if ( ! empty( $results ) ) {
            foreach ( $results as &$row ) {
                $product                = wc_get_product( $row['product_id'] );
                $row['product_name']    = $product ? $product->get_name() : __( 'Deleted product', 'preview-ai' );
                $row['conversion_rate'] = $row['previews'] > 0 ? round( ( $row['conversions'] / $row['previews'] ) * 100, 1 ) : 0;
            }
        }

        wp_cache_set( $cache_key, $results, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $results;
    }

    /**
     * Get recent conversions.
     *
     * @param int $limit Limit.
     * @return array
     */
    public static function get_recent_conversions( $limit = 10 ) {
        global $wpdb;

        $limit     = absint( $limit );
        $cache_key = 'recent_conversions_' . $limit;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            return $cached;
        }

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT e.*, u.display_name, u.user_email
                FROM {$table} e
                LEFT JOIN {$wpdb->users} u ON e.user_id = u.ID
                WHERE e.event_type = %s
                ORDER BY e.created_at DESC
                LIMIT %d",
                'conversion',
                $limit
            ),
            ARRAY_A
        );

        if ( ! empty( $results ) ) {
            foreach ( $results as &$row ) {
                $product              = wc_get_product( $row['product_id'] );
                $row['product_name']  = $product ? $product->get_name() : __( 'Deleted product', 'preview-ai' );
                $row['customer_name'] = ! empty( $row['display_name'] ) ? $row['display_name'] : __( 'Guest', 'preview-ai' );
            }
        }

        wp_cache_set( $cache_key, $results, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $results;
    }

    /**
     * Get user stats.
     *
     * @param int $user_id User ID.
     * @return array
     */
    public static function get_user_stats( $user_id ) {
        global $wpdb;

        $user_id   = absint( $user_id );
        $cache_key = 'user_stats_' . $user_id;
        $cached    = wp_cache_get( $cache_key, self::CACHE_GROUP );
        if ( false !== $cached ) {
            return $cached;
        }

        $table = self::get_table_name();

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $stats = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT 
                    SUM(CASE WHEN event_type = 'preview' THEN 1 ELSE 0 END) as previews,
                    SUM(CASE WHEN event_type = 'conversion' THEN 1 ELSE 0 END) as conversions,
                    SUM(CASE WHEN event_type = 'conversion' THEN order_total ELSE 0 END) as total_spent
                FROM {$table}
                WHERE user_id = %d",
                $user_id
            ),
            ARRAY_A
        );

        $result = array(
            'previews'    => (int) ( $stats['previews'] ?? 0 ),
            'conversions' => (int) ( $stats['conversions'] ?? 0 ),
            'total_spent' => (float) ( $stats['total_spent'] ?? 0 ),
        );

        wp_cache_set( $cache_key, $result, self::CACHE_GROUP, 5 * MINUTE_IN_SECONDS );
        return $result;
    }
}