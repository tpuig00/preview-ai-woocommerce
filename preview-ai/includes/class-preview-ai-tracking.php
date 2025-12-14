<?php
/**
 * Simple conversion tracking.
 *
 * @package Preview_Ai
 */

class PREVIEW_AI_Tracking {

	const OPTION_STATS = 'preview_ai_stats';

	/**
	 * Save product_id to session when preview is generated.
	 *
	 * @param int $product_id Product ID.
	 */
	public static function track_preview( $product_id ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$previewed = WC()->session->get( 'preview_ai_previewed', array() );
		if ( ! in_array( $product_id, $previewed, true ) ) {
			$previewed[] = $product_id;
			WC()->session->set( 'preview_ai_previewed', $previewed );
		}

		self::increment_stat( 'previews' );
	}

	/**
	 * Save previewed products to order meta.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function save_to_order( $order_id ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		$previewed = WC()->session->get( 'preview_ai_previewed', array() );
		if ( ! empty( $previewed ) ) {
			update_post_meta( $order_id, '_preview_ai_previewed', $previewed );
			WC()->session->set( 'preview_ai_previewed', array() ); // Clear session.
		}
	}

	/**
	 * Track conversion when order is completed.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function track_completed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		// Already tracked.
		if ( get_post_meta( $order_id, '_preview_ai_converted', true ) ) {
			return;
		}

		$previewed = get_post_meta( $order_id, '_preview_ai_previewed', true );
		if ( empty( $previewed ) ) {
			return;
		}

		// Check if any item was previewed.
		foreach ( $order->get_items() as $item ) {
			$product_id = $item->get_product_id();
			if ( in_array( $product_id, $previewed, true ) ) {
				self::increment_stat( 'conversions' );
				update_post_meta( $order_id, '_preview_ai_converted', true );
				return;
			}
		}
	}

	/**
	 * Track refund if it was a conversion.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function track_refunded( $order_id ) {
		if ( get_post_meta( $order_id, '_preview_ai_converted', true ) ) {
			self::increment_stat( 'refunds' );
		}
	}

	/**
	 * Increment a stat counter.
	 *
	 * @param string $key Stat key.
	 */
	private static function increment_stat( $key ) {
		$stats = get_option( self::OPTION_STATS, array() );
		$stats[ $key ] = isset( $stats[ $key ] ) ? $stats[ $key ] + 1 : 1;
		update_option( self::OPTION_STATS, $stats, false );
	}

	/**
	 * Get stats.
	 *
	 * @return array Stats.
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
}

