<?php
/**
 * Logger utility for Preview AI plugin.
 *
 * Provides simple logging functions for development and debugging.
 *
 * @link       https://previewai.app
 * @since      1.0.0
 *
 * @package    Preview_Ai
 * @subpackage Preview_Ai/includes
 */

class PREVIEW_AI_Logger {

	/**
	 * Log levels constants.
	 */
	const ERROR = 'ERROR';
	const WARNING = 'WARNING';
	const INFO = 'INFO';
	const DEBUG = 'DEBUG';

	/**
	 * Log an error message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function error( $message, $context = array() ) {
		self::log( self::ERROR, $message, $context );
	}

	/**
	 * Log a warning message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function warning( $message, $context = array() ) {
		self::log( self::WARNING, $message, $context );
	}

	/**
	 * Log an info message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function info( $message, $context = array() ) {
		self::log( self::INFO, $message, $context );
	}

	/**
	 * Log a debug message.
	 *
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	public static function debug( $message, $context = array() ) {
		// Only log debug messages if WP_DEBUG is enabled.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			self::log( self::DEBUG, $message, $context );
		}
	}

	/**
	 * Log a message with specified level.
	 *
	 * @param string $level   Log level (ERROR, WARNING, INFO, DEBUG).
	 * @param string $message Log message.
	 * @param array  $context Additional context data.
	 */
	private static function log( $level, $message, $context = array() ) {
		$timestamp = current_time( 'Y-m-d H:i:s' );

		$log_message = sprintf(
			'[%s] PREVIEW_AI %s: %s',
			$timestamp,
			$level,
			$message
		);

		// Add context if provided.
		if ( ! empty( $context ) ) {
			$log_message .= ' | Context: ' . wp_json_encode( $context );
		}

		// Use WordPress error_log function.
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $log_message );
	}
}
