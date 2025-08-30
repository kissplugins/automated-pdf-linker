<?php
/**
 * File Scanner Service
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Services;

use KissPlugins\AutomatedPdfLinker\Utils\FileNormalizer;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;

/**
 * File Scanner Class
 *
 * Handles scanning directories for PDF files.
 *
 * @since 3.0.0
 */
class FileScanner {

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
     * Scan selected directories for PDF files.
     *
     * @param array $selected_directories Array of directory names to scan.
     * @return array Array of PDF file information.
     * @throws Exception If scanning fails.
     */
    public function scan_directories( array $selected_directories ): array {
        if ( empty( $selected_directories ) ) {
            $this->logger->info( 'No directories selected for scanning.' );
            return [];
        }

        $upload_dir_info = wp_upload_dir();
        $uploads_base_path = trailingslashit( $upload_dir_info['basedir'] );
        $pdf_files = [];

        $this->logger->info( 'Starting PDF scan of ' . count( $selected_directories ) . ' directories.' );

        foreach ( $selected_directories as $dir_slug ) {
            try {
                $directory_files = $this->scan_single_directory( $uploads_base_path, $dir_slug );
                $pdf_files = array_merge( $pdf_files, $directory_files );
                
                $this->logger->debug( 
                    "Scanned directory '{$dir_slug}': found " . count( $directory_files ) . ' PDF files.' 
                );
            } catch ( Exception $e ) {
                $this->logger->error( "Error scanning directory '{$dir_slug}': " . $e->getMessage() );
                throw $e;
            }
        }

        // Sort the files alphabetically by relative path for consistency
        usort( $pdf_files, function( $a, $b ) {
            return strcmp( $a['path'], $b['path'] );
        });

        $this->logger->info( 'PDF scan completed. Found ' . count( $pdf_files ) . ' total PDF files.' );

        return $pdf_files;
    }

    /**
     * Scan a single directory for PDF files.
     *
     * @param string $uploads_base_path Base uploads directory path.
     * @param string $dir_slug          Directory name to scan.
     * @return array Array of PDF file information.
     * @throws Exception If directory scanning fails.
     */
    private function scan_single_directory( string $uploads_base_path, string $dir_slug ): array {
        $scan_path = $uploads_base_path . $dir_slug;

        if ( ! is_dir( $scan_path ) || ! is_readable( $scan_path ) ) {
            $this->logger->warn( "Directory not found or readable: {$scan_path}" );
            return [];
        }

        $pdf_files = [];

        try {
            // Use RecursiveDirectoryIterator to scan subdirectories
            $directory_iterator = new RecursiveDirectoryIterator( 
                $scan_path, 
                RecursiveDirectoryIterator::SKIP_DOTS 
            );
            
            // Use RecursiveIteratorIterator to flatten the structure
            $file_iterator = new RecursiveIteratorIterator( 
                $directory_iterator, 
                RecursiveIteratorIterator::LEAVES_ONLY 
            );

            foreach ( $file_iterator as $fileinfo ) {
                if ( $this->is_pdf_file( $fileinfo ) ) {
                    $pdf_files[] = $this->create_file_info( $fileinfo, $uploads_base_path );
                }
            }
        } catch ( Exception $e ) {
            throw new Exception( 
                sprintf( 
                    'Error scanning directory %s: %s', 
                    $dir_slug, 
                    $e->getMessage() 
                ) 
            );
        }

        return $pdf_files;
    }

    /**
     * Check if a file is a readable PDF file.
     *
     * @param \SplFileInfo $fileinfo File information object.
     * @return bool True if file is a readable PDF, false otherwise.
     */
    private function is_pdf_file( \SplFileInfo $fileinfo ): bool {
        return $fileinfo->isFile() && 
               $fileinfo->isReadable() && 
               'pdf' === strtolower( $fileinfo->getExtension() );
    }

    /**
     * Create file information array.
     *
     * @param \SplFileInfo $fileinfo          File information object.
     * @param string       $uploads_base_path Base uploads directory path.
     * @return array File information array.
     */
    private function create_file_info( \SplFileInfo $fileinfo, string $uploads_base_path ): array {
        $full_path = $fileinfo->getPathname();
        $relative_path = str_replace( $uploads_base_path, '', $full_path );
        $filename = $fileinfo->getFilename();
        $normalized_name = FileNormalizer::normalize( $filename );

        return [
            'path'            => $relative_path,  // e.g., '2024/04/document.pdf' or 'coas/report.pdf'
            'filename'        => $filename,       // e.g., 'document.pdf'
            'normalized_name' => $normalized_name // e.g., 'document'
        ];
    }

    /**
     * Get available directories in uploads folder.
     *
     * @return array Array of available directory names.
     */
    public function get_available_directories(): array {
        $upload_dir_info = wp_upload_dir();
        $uploads_path = $upload_dir_info['basedir'];
        $available_dirs = [];

        if ( ! is_dir( $uploads_path ) || ! is_readable( $uploads_path ) ) {
            $this->logger->error( 'Uploads directory is not readable or does not exist.' );
            return [];
        }

        try {
            $iterator = new \DirectoryIterator( $uploads_path );
            foreach ( $iterator as $fileinfo ) {
                // Only include immediate subdirectories, skip dots and files
                if ( $fileinfo->isDir() && ! $fileinfo->isDot() ) {
                    $available_dirs[] = $fileinfo->getFilename();
                }
            }
            sort( $available_dirs ); // Sort alphabetically
        } catch ( Exception $e ) {
            $this->logger->error( 'Error scanning uploads directory: ' . $e->getMessage() );
            return [];
        }

        $this->logger->debug( 'Found ' . count( $available_dirs ) . ' available directories in uploads.' );

        return $available_dirs;
    }
}
