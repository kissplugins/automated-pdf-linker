<?php
/**
 * Plugin Deactivator
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Core;

/**
 * Plugin Deactivator Class
 *
 * Handles plugin deactivation tasks.
 *
 * @since 3.0.0
 */
class Deactivator {

    /**
     * Deactivate the plugin.
     *
     * Consider whether to remove settings or the index on deactivation.
     * Typically, settings are kept unless there's a specific "uninstall" routine.
     * We won't delete the index here, allowing reactivation without data loss.
     *
     * @since 3.0.0
     * @return void
     */
    public static function deactivate(): void {
        self::clear_scheduled_hooks();
        self::cleanup_temporary_data();
    }

    /**
     * Clear any scheduled hooks or cron jobs.
     *
     * @return void
     */
    private static function clear_scheduled_hooks(): void {
        // Clear any scheduled actions if background processing was added
        wp_clear_scheduled_hook( 'kapl_background_index_hook' );
    }

    /**
     * Clean up temporary data that doesn't need to persist.
     *
     * @return void
     */
    private static function cleanup_temporary_data(): void {
        // Remove activation flag
        delete_option( 'kapl_activation_flag' );
        
        // Clean up any temporary transients
        delete_transient( 'kapl_temp_data' );
    }
}
