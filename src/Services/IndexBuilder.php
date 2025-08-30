<?php
/**
 * Index Builder Service
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Services;

use KissPlugins\AutomatedPdfLinker\Utils\Logger;
use WP_Error;

/**
 * Index Builder Class
 *
 * Handles building and managing the PDF index.
 *
 * @since 3.0.0
 */
class IndexBuilder {

    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private $cache_manager;

    /**
     * File scanner instance.
     *
     * @var FileScanner
     */
    private $file_scanner;

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
        $this->file_scanner  = new FileScanner( $logger );
        $this->logger        = $logger;
    }

    /**
     * Build PDF index from selected directories.
     *
     * @param array $selected_directories Array of directory names to scan.
     * @return int|WP_Error Number of PDF files indexed on success, or WP_Error on failure.
     */
    public function build_index( array $selected_directories ): int|WP_Error {
        $this->logger->info( 'Starting PDF index build process.' );

        if ( empty( $selected_directories ) ) {
            // If no directories are selected, clear the index and return 0 count
            $this->cache_manager->clear_index();
            $this->logger->info( 'No directories selected, index cleared.' );
            return 0;
        }

        try {
            $pdf_files = $this->file_scanner->scan_directories( $selected_directories );
            
            $update_success = $this->cache_manager->update_index( $pdf_files );
            
            if ( ! $update_success ) {
                $error_message = __( 'Failed to save the PDF index to the database.', 'kiss-automated-pdf-linker' );
                $this->logger->error( $error_message );
                return new WP_Error( 'save_error', $error_message );
            }

            $file_count = count( $pdf_files );
            $this->logger->info( "PDF index build completed successfully. Indexed {$file_count} files." );
            
            return $file_count;
            
        } catch ( \Exception $e ) {
            $error_message = sprintf(
                /* translators: %s: Error message */
                __( 'Error building PDF index: %s', 'kiss-automated-pdf-linker' ),
                $e->getMessage()
            );
            
            $this->logger->error( $error_message );
            return new WP_Error( 'build_error', $error_message );
        }
    }

    /**
     * Get current PDF index.
     *
     * @return array|null PDF index data or null if not found.
     */
    public function get_index(): ?array {
        return $this->cache_manager->get_index();
    }

    /**
     * Clear PDF index.
     *
     * @return bool True on success, false on failure.
     */
    public function clear_index(): bool {
        $this->logger->info( 'Clearing PDF index.' );
        return $this->cache_manager->clear_index();
    }

    /**
     * Get index statistics.
     *
     * @return array Index statistics.
     */
    public function get_index_stats(): array {
        $index = $this->get_index();
        
        if ( null === $index ) {
            return [
                'total_files' => 0,
                'status'      => 'not_found',
                'message'     => __( 'No index found.', 'kiss-automated-pdf-linker' ),
            ];
        }

        if ( empty( $index ) ) {
            return [
                'total_files' => 0,
                'status'      => 'empty',
                'message'     => __( 'Index is empty.', 'kiss-automated-pdf-linker' ),
            ];
        }

        $total_files = count( $index );
        
        return [
            'total_files' => $total_files,
            'status'      => 'ready',
            'message'     => sprintf(
                /* translators: %d: number of files in the index */
                __( 'Index contains %d PDF files.', 'kiss-automated-pdf-linker' ),
                $total_files
            ),
        ];
    }

    /**
     * Validate index integrity.
     *
     * @return array Validation results.
     */
    public function validate_index(): array {
        $index = $this->get_index();
        $validation_results = [
            'is_valid'     => true,
            'total_files'  => 0,
            'valid_files'  => 0,
            'invalid_files' => 0,
            'errors'       => [],
        ];

        if ( null === $index ) {
            $validation_results['is_valid'] = false;
            $validation_results['errors'][] = __( 'Index not found.', 'kiss-automated-pdf-linker' );
            return $validation_results;
        }

        $validation_results['total_files'] = count( $index );

        foreach ( $index as $item ) {
            if ( $this->is_valid_index_item( $item ) ) {
                $validation_results['valid_files']++;
            } else {
                $validation_results['invalid_files']++;
                $validation_results['is_valid'] = false;
                $validation_results['errors'][] = sprintf(
                    /* translators: %s: invalid item description */
                    __( 'Invalid index item: %s', 'kiss-automated-pdf-linker' ),
                    wp_json_encode( $item )
                );
            }
        }

        return $validation_results;
    }

    /**
     * Validate index item structure.
     *
     * @param mixed $item Index item to validate.
     * @return bool True if valid, false otherwise.
     */
    private function is_valid_index_item( $item ): bool {
        return is_array( $item ) &&
               isset( $item['path'] ) &&
               isset( $item['filename'] ) &&
               isset( $item['normalized_name'] ) &&
               is_string( $item['path'] ) &&
               is_string( $item['filename'] ) &&
               is_string( $item['normalized_name'] );
    }

    /**
     * Get available directories for scanning.
     *
     * @return array Array of available directory names.
     */
    public function get_available_directories(): array {
        return $this->file_scanner->get_available_directories();
    }
}
