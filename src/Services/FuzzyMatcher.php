<?php
/**
 * Fuzzy Matcher Service
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Services;

use KissPlugins\AutomatedPdfLinker\Utils\FileNormalizer;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Fuzzy Matcher Class
 *
 * Handles fuzzy matching logic for finding PDF files.
 *
 * @since 3.0.0
 */
class FuzzyMatcher {

    /**
     * Minimum percentage similarity required for a fuzzy match.
     */
    const SIMILARITY_THRESHOLD = 50;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct( Logger $logger ) {
        $this->logger = $logger;
    }

    /**
     * Find the best matching PDF file from the index.
     *
     * @param string $search_name Search term to match against.
     * @param array  $pdf_index   PDF index data.
     * @return array|null Best matching index item or null if no match found.
     */
    public function find_best_match( string $search_name, array $pdf_index ): ?array {
        if ( empty( $search_name ) || empty( $pdf_index ) ) {
            return null;
        }

        $normalized_search_name = FileNormalizer::normalize_with_logging( $search_name, $this->logger );
        $best_match_item = null;
        $highest_similarity = -1;

        foreach ( $pdf_index as $index_item ) {
            if ( ! $this->is_valid_index_item( $index_item ) ) {
                continue;
            }

            $similarity_percent = $this->calculate_similarity( 
                $normalized_search_name, 
                $index_item['normalized_name'] 
            );

            if ( $similarity_percent >= self::SIMILARITY_THRESHOLD && $similarity_percent > $highest_similarity ) {
                $highest_similarity = $similarity_percent;
                $best_match_item = $index_item;
                
                $this->logger->debug( 
                    "New best match: '{$index_item['filename']}' with {$similarity_percent}% similarity" 
                );
            }
        }

        if ( null !== $best_match_item ) {
            $this->logger->info( 
                "Best match found: '{$best_match_item['filename']}' with {$highest_similarity}% similarity" 
            );
        } else {
            $this->logger->info( "No match found for search term: '{$search_name}'" );
        }

        return $best_match_item;
    }

    /**
     * Find matching PDF for product title (substring matching).
     *
     * @param string $product_title Product title to match.
     * @param array  $pdf_index     PDF index data.
     * @return array|null Matching index item or null if no match found.
     */
    public function find_product_match( string $product_title, array $pdf_index ): ?array {
        if ( empty( $product_title ) || empty( $pdf_index ) ) {
            return null;
        }

        $normalized_product_title = FileNormalizer::normalize_with_logging( $product_title, $this->logger );

        foreach ( $pdf_index as $index_item ) {
            if ( ! $this->is_valid_index_item( $index_item ) ) {
                continue;
            }

            $normalized_file_name = $index_item['normalized_name'];
            
            // Check if the normalized product title is a substring of the normalized file name
            if ( false !== strpos( $normalized_file_name, $normalized_product_title ) ) {
                $this->logger->info( 
                    "Product match found: '{$index_item['filename']}' for product '{$product_title}'" 
                );
                return $index_item;
            }
        }

        $this->logger->info( "No product match found for: '{$product_title}'" );
        return null;
    }

    /**
     * Calculate similarity between two normalized strings.
     *
     * @param string $search_name     Normalized search term.
     * @param string $normalized_name Normalized filename.
     * @return float Similarity percentage.
     */
    private function calculate_similarity( string $search_name, string $normalized_name ): float {
        $similarity_percent = 0;
        similar_text( $search_name, $normalized_name, $similarity_percent );
        return $similarity_percent;
    }

    /**
     * Validate index item structure.
     *
     * @param mixed $index_item Index item to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_index_item( $index_item ): bool {
        return is_array( $index_item ) &&
               isset( $index_item['normalized_name'] ) &&
               isset( $index_item['path'] ) &&
               isset( $index_item['filename'] );
    }

    /**
     * Get similarity threshold.
     *
     * @return int Similarity threshold percentage.
     */
    public function get_similarity_threshold(): int {
        return self::SIMILARITY_THRESHOLD;
    }
}
