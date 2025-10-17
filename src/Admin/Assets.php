<?php
/**
 * Admin Assets
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

/**
 * Admin Assets Class
 *
 * Handles admin-side asset enqueuing.
 *
 * @since 3.0.0
 */
class Assets {

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook suffix.
     * @return void
     */
    public static function enqueue_scripts( string $hook ): void {
        // Only load on our settings page
        if ( 'settings_page_' . Settings::SETTINGS_SLUG !== $hook ) {
            return;
        }

        self::enqueue_admin_styles();
        self::enqueue_color_picker();
    }

    /**
     * Enqueue admin styles.
     *
     * @return void
     */
    private static function enqueue_admin_styles(): void {
        $plugin_url = \plugin_dir_url( dirname( dirname( __DIR__ ) ) . '/kiss-automated-pdf-linker-v3.php' );

        \wp_enqueue_style(
            'kapl-admin-styles',
            $plugin_url . 'assets/admin.css',
            [],
            '3.0.1'
        );
    }

    /**
     * Enqueue color picker assets.
     *
     * @return void
     */
    private static function enqueue_color_picker(): void {
        // Enqueue WordPress color picker
        \wp_enqueue_style( 'wp-color-picker' );
        \wp_enqueue_script( 'wp-color-picker' );

        // Enqueue our custom script to initialize color picker
        \wp_enqueue_script(
            'kapl-admin-script',
            false, // We'll use inline script instead of a separate file
            [ 'wp-color-picker' ],
            '3.0.0',
            true
        );

        // Initialize color picker
        \wp_add_inline_script( 'kapl-admin-script', '
            jQuery(document).ready(function($) {
                $(".kapl-color-picker").wpColorPicker();
            });
        ' );
    }
}
