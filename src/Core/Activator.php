<?php
/**
 * Plugin Activator
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Core;

/**
 * Plugin Activator Class
 *
 * Handles plugin activation tasks.
 *
 * @since 3.0.0
 */
class Activator {

    /**
     * Settings option name.
     */
    const SETTINGS_OPTION_NAME = 'kapl_settings';

    /**
     * Activate the plugin.
     *
     * Sets up default settings if they don't exist.
     * Does NOT automatically build the index on activation, as it could be slow.
     *
     * @since 3.0.0
     * @return void
     */
    public static function activate(): void {
        self::create_default_settings();
        self::set_activation_flag();
    }

    /**
     * Create default settings if they don't exist.
     *
     * @return void
     */
    private static function create_default_settings(): void {
        if ( false === get_option( self::SETTINGS_OPTION_NAME ) ) {
            update_option( self::SETTINGS_OPTION_NAME, [
                'selected_directories'     => [],
                'link_color'              => '#0000FF',
                'use_product_title_match' => false,
                'debug_logging'           => false,
            ] );
        }
    }

    /**
     * Set activation flag for any post-activation tasks.
     *
     * @return void
     */
    private static function set_activation_flag(): void {
        update_option( 'kapl_activation_flag', true );
    }
}
