<?php
/**
 * Frontend Assets
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Frontend;

/**
 * Frontend Assets Class
 *
 * Handles frontend asset enqueuing.
 *
 * @since 3.0.0
 */
class Assets {

    /**
     * Enqueue frontend styles.
     *
     * @return void
     */
    public static function enqueue_styles(): void {
        $settings = \get_option( 'kapl_settings', [ 'link_color' => '#0000FF' ] );
        $link_color = isset( $settings['link_color'] ) ? $settings['link_color'] : '#0000FF';

        // Add inline CSS for the PDF links
        $custom_css = "
            .single-product .woocommerce-tabs.accordion-type ul.tabs > li .woocommerce-Tabs-panel a.kapl-pdf-link,
            .kapl-pdf-link {
                color: {$link_color} !important;
                text-decoration: underline;
            }
            
            .kapl-pdf-link:hover {
                opacity: 0.8;
            }
        ";

        \wp_register_style( 'kapl-frontend-styles', false ); // Register an empty handle
        \wp_enqueue_style( 'kapl-frontend-styles' );
        \wp_add_inline_style( 'kapl-frontend-styles', $custom_css );
    }
}
