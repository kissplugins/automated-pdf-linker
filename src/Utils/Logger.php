<?php
/**
 * Logger Utility
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Utils;

/**
 * Logger Class
 *
 * Handles debug logging functionality.
 *
 * @since 3.0.0
 */
class Logger {

    /**
     * Settings option name.
     */
    const SETTINGS_OPTION_NAME = 'kapl_settings';

    /**
     * Log levels.
     */
    const LEVEL_DEBUG = 'debug';
    const LEVEL_INFO  = 'info';
    const LEVEL_WARN  = 'warn';
    const LEVEL_ERROR = 'error';

    /**
     * Write a debug message to the PHP error log when debugging is enabled.
     *
     * @since 3.0.0
     * @param string $message Message to log.
     * @param string $level   Log level (debug, info, warn, error).
     * @return void
     */
    public function log( string $message, string $level = self::LEVEL_DEBUG ): void {
        if ( ! $this->is_logging_enabled() ) {
            return;
        }

        $formatted_message = $this->format_message( $message, $level );
        error_log( $formatted_message );
    }

    /**
     * Log debug message.
     *
     * @param string $message Message to log.
     * @return void
     */
    public function debug( string $message ): void {
        $this->log( $message, self::LEVEL_DEBUG );
    }

    /**
     * Log info message.
     *
     * @param string $message Message to log.
     * @return void
     */
    public function info( string $message ): void {
        $this->log( $message, self::LEVEL_INFO );
    }

    /**
     * Log warning message.
     *
     * @param string $message Message to log.
     * @return void
     */
    public function warn( string $message ): void {
        $this->log( $message, self::LEVEL_WARN );
    }

    /**
     * Log error message.
     *
     * @param string $message Message to log.
     * @return void
     */
    public function error( string $message ): void {
        $this->log( $message, self::LEVEL_ERROR );
    }

    /**
     * Check if logging is enabled in settings.
     *
     * @return bool
     */
    private function is_logging_enabled(): bool {
        $settings = get_option( self::SETTINGS_OPTION_NAME, [ 'debug_logging' => false ] );
        return isset( $settings['debug_logging'] ) && $settings['debug_logging'];
    }

    /**
     * Format log message with timestamp and level.
     *
     * @param string $message Message to format.
     * @param string $level   Log level.
     * @return string Formatted message.
     */
    private function format_message( string $message, string $level ): string {
        $timestamp = current_time( 'Y-m-d H:i:s' );
        $level_upper = strtoupper( $level );
        
        return sprintf( '[%s] KAPL %s: %s', $timestamp, $level_upper, $message );
    }
}
