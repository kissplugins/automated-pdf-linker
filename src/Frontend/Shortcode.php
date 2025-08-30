<?php
/**
 * Shortcode Handler
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Frontend;

use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Services\FuzzyMatcher;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Shortcode Class
 *
 * Handles the [kiss_pdf] shortcode and WooCommerce integration.
 *
 * @since 3.0.0
 */
class Shortcode {

    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private $cache_manager;

    /**
     * Fuzzy matcher instance.
     *
     * @var FuzzyMatcher
     */
    private $fuzzy_matcher;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param CacheManager $cache_manager Cache manager instance.
     * @param Logger       $logger        Logger instance.
     */
    public function __construct( CacheManager $cache_manager, Logger $logger ) {
        $this->cache_manager = $cache_manager;
        $this->fuzzy_matcher = new FuzzyMatcher( $logger );
        $this->logger        = $logger;
    }

    /**
     * Handle the [kiss_pdf] shortcode.
     *
     * @param array|string $atts    Shortcode attributes.
     * @param string|null  $content Content enclosed within the shortcode.
     * @param string       $tag     Shortcode tag name.
     * @return string HTML output for the shortcode.
     */
    public function handle( $atts, $content = null, $tag = '' ): string {
        // Normalize attribute keys to lowercase
        $atts = array_change_key_case( (array) $atts, CASE_LOWER );

        // Define defaults and merge with user attributes
        $atts = \shortcode_atts(
            [ 'name' => '' ],
            $atts,
            $tag
        );

        // Sanitize the search term
        $search_name = \sanitize_text_field( $atts['name'] );

        // Validate input
        if ( empty( $search_name ) ) {
            return '<em class="kapl-error">' . \esc_html__( 'Shortcode error: Missing "name" attribute.', 'kiss-automated-pdf-linker' ) . '</em>';
        }

        // Get PDF index
        $pdf_index = $this->cache_manager->get_index();

        if ( null === $pdf_index ) {
            return '<em class="kapl-error">' . \esc_html__( 'PDF index not found. Please ask an administrator to rebuild it.', 'kiss-automated-pdf-linker' ) . '</em>';
        }

        if ( empty( $pdf_index ) ) {
            return '<em>' . \esc_html__( 'No PDF files found in the index.', 'kiss-automated-pdf-linker' ) . '</em>';
        }

        // Find best match
        $best_match_item = $this->fuzzy_matcher->find_best_match( $search_name, $pdf_index );

        if ( null === $best_match_item ) {
            return '<em>' . \esc_html__( 'No matching PDF file found.', 'kiss-automated-pdf-linker' ) . '</em>';
        }

        return $this->generate_pdf_link( $best_match_item );
    }

    /**
     * Generate HTML link for PDF file.
     *
     * @param array $pdf_item PDF index item.
     * @return string HTML link.
     */
    private function generate_pdf_link( array $pdf_item ): string {
        $upload_dir_info = \wp_upload_dir();
        $uploads_base_url = \trailingslashit( $upload_dir_info['baseurl'] );

        // Construct the full URL
        $file_url = $uploads_base_url . \ltrim( $pdf_item['path'], '/' );

        // Use the original filename without extension as link text
        $link_text = \pathinfo( $pdf_item['filename'], PATHINFO_FILENAME );
        if ( empty( $link_text ) ) {
            $link_text = $pdf_item['filename']; // Fallback
        }

        return sprintf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="kapl-pdf-link">%s</a>',
            \esc_url( $file_url ),
            \esc_html( $link_text )
        );
    }

    /**
     * Customize WooCommerce strain tab.
     *
     * @param array $tabs WooCommerce product tabs.
     * @return array Modified tabs.
     */
    public function customize_strain_tab( array $tabs ): array {
        global $product;
        
        if ( ! $product ) {
            return $tabs;
        }

        $product_id = $product->get_id();
        $settings = \get_option( 'kapl_settings', [ 'use_product_title_match' => false ] );

        if ( \get_field( 'product_strains', $product_id ) && $settings['use_product_title_match'] ) {
            $tabs['strains']['callback'] = [ $this, 'strain_tab_content' ];
        }

        return $tabs;
    }

    /**
     * Render strain tab content with PDF links.
     *
     * @return void
     */
    public function strain_tab_content(): void {
        global $product;
        
        if ( ! $product ) {
            return;
        }

        $product_id = $product->get_id();
        $disable_pdf_linking = \get_field( 'disable_coas_pdf_links', $product_id );
        $strain_fields = \get_field( 'product_strains', $product_id );

        if ( $disable_pdf_linking ) {
            echo $strain_fields;
            return;
        }

        $product_title = $product->get_name();
        $pdf_index = $this->cache_manager->get_index();
        $can_link_pdfs = ( null !== $pdf_index && ! empty( $pdf_index ) );

        echo '<div class="product-strains">';

        if ( $can_link_pdfs && ! empty( $product_title ) ) {
            $this->render_strain_content_with_links( $strain_fields, $product_title, $pdf_index );
        } else {
            echo \wp_kses_post( $strain_fields );
        }

        echo '</div>';
    }

    /**
     * Render strain content with PDF links.
     *
     * @param string $strain_fields Strain fields content.
     * @param string $product_title Product title.
     * @param array  $pdf_index     PDF index.
     * @return void
     */
    private function render_strain_content_with_links( string $strain_fields, string $product_title, array $pdf_index ): void {
        $p_tag_pattern = '/<p>(.*?)<\/p>/i';
        \preg_match_all( $p_tag_pattern, $strain_fields, $matches );

        if ( empty( $matches[1] ) ) {
            echo \wp_kses_post( $strain_fields );
            return;
        }

        $product_match = $this->fuzzy_matcher->find_product_match( $product_title, $pdf_index );

        foreach ( $matches[1] as $line_content ) {
            $decoded_line_content = \html_entity_decode( $line_content, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
            $line = trim( \strip_tags( $decoded_line_content ) );

            if ( empty( $line ) ) {
                continue;
            }

            echo '<p>';

            if ( $product_match ) {
                $this->render_strain_line_with_link( $line, $product_match );
            } else {
                echo \esc_html( $line );
            }

            echo '</p>';
        }
    }

    /**
     * Render strain line with PDF link.
     *
     * @param string $line         Strain line content.
     * @param array  $product_match Matched PDF item.
     * @return void
     */
    private function render_strain_line_with_link( string $line, array $product_match ): void {
        // Parse strain name and details for display purposes
        $strain_name_display = $line; // Default to full line if no separator
        $strain_details_display = '';

        $strain_split_pattern = '/^(.+?)([\s]*[^a-zA-Z0-9\\s].*)$/u';
        if ( \preg_match( $strain_split_pattern, $line, $split_matches ) ) {
            $strain_name_display = trim( $split_matches[1] );
            $strain_details_display = $split_matches[2];
        } else {
            $pos = \strpos( $line, '-' );
            if ( false !== $pos ) {
                $strain_name_display = trim( \substr( $line, 0, $pos ) );
                $strain_details_display = \substr( $line, $pos );
            }
        }

        $upload_dir_info = \wp_upload_dir();
        $uploads_base_url = \trailingslashit( $upload_dir_info['baseurl'] );
        $file_url = $uploads_base_url . \ltrim( $product_match['path'], '/' );

        printf(
            '<a href="%s" target="_blank" rel="noopener noreferrer" class="kapl-pdf-link">%s</a>%s',
            \esc_url( $file_url ),
            \esc_html( $strain_name_display ),
            \esc_html( $strain_details_display )
        );
    }
}
