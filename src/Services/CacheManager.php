<?php
/**
 * Cache Manager Service
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Services;

use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Cache Manager Class
 *
 * Handles PDF index caching with multiple storage strategies.
 *
 * @since 3.0.0
 */
class CacheManager {

    /**
     * Index option name.
     */
    const INDEX_OPTION_NAME = 'kapl_pdf_index';

    /**
     * Index file path.
     */
    const INDEX_FILE_PATH = 'pdf-index.json';

    /**
     * Index version option name.
     */
    const INDEX_VERSION_OPTION_NAME = 'kapl_pdf_index_version';

    /**
     * Current index version.
     */
    const CURRENT_INDEX_VERSION = '3.1.0';

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Plugin directory path.
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * Constructor.
     *
     * @param Logger $logger Logger instance.
     */
    public function __construct( Logger $logger ) {
        $this->logger = $logger;

        // Set plugin directory with fallback
        if ( function_exists( 'plugin_dir_path' ) ) {
            // Try to get from WordPress constant first
            if ( defined( 'KAPL_PLUGIN_DIR' ) ) {
                $this->plugin_dir = KAPL_PLUGIN_DIR;
            } else {
                // Fallback to calculating from current file
                $this->plugin_dir = plugin_dir_path( dirname( dirname( __DIR__ ) ) . '/kiss-automated-pdf-linker-v3.php' );
            }
        } else {
            // Fallback when WordPress functions aren't available
            $this->plugin_dir = dirname( dirname( __DIR__ ) ) . '/';
        }

        $this->logger->debug( 'CacheManager initialized with plugin_dir: ' . $this->plugin_dir );
    }

    /**
     * Get PDF index from cache.
     *
     * @return array|null PDF index data or null if not found.
     */
    public function get_index(): ?array {
        // If WordPress functions aren't available, use file storage
        if ( ! function_exists( 'get_option' ) ) {
            $this->logger->debug( 'WordPress functions not available, using file storage.' );
            return $this->load_index_from_file();
        }

        // Check if we're using file-based storage
        $is_file_storage = \get_option( self::INDEX_OPTION_NAME . '_file_storage', false );

        if ( $is_file_storage ) {
            $this->logger->debug( 'Using file-based storage for PDF index.' );
            return $this->load_index_from_file();
        }

        $index_json = \get_option( self::INDEX_OPTION_NAME, null );
        if ( null === $index_json ) {
            // If no database option, try file storage as fallback
            $this->logger->debug( 'No database option found, trying file storage.' );
            return $this->load_index_from_file();
        }

        // Check if data is compressed
        $is_compressed = \get_option( self::INDEX_OPTION_NAME . '_compressed', false );

        if ( $is_compressed ) {
            return $this->decompress_index( $index_json );
        }

        return $this->decode_index( $index_json );
    }

    /**
     * Check if index needs migration to include new metadata fields.
     *
     * @return bool True if index needs migration, false otherwise.
     */
    public function needs_index_migration(): bool {
        if ( ! function_exists( 'get_option' ) ) {
            return false;
        }

        $index_version = \get_option( self::INDEX_VERSION_OPTION_NAME, '3.0.0' );
        $needs_migration = version_compare( $index_version, self::CURRENT_INDEX_VERSION, '<' );

        if ( $needs_migration ) {
            $this->logger->info( "Index migration needed: current version {$index_version}, target version " . self::CURRENT_INDEX_VERSION );
        }

        return $needs_migration;
    }

    /**
     * Update PDF index in cache.
     *
     * @param array $index_data PDF index data.
     * @return bool True on success, false on failure.
     */
    public function update_index( array $index_data ): bool {
        $this->logger->debug( 'Updating PDF index with ' . count( $index_data ) . ' items.' );

        // Use WordPress JSON encoding if available, otherwise use PHP's json_encode
        if ( function_exists( 'wp_json_encode' ) ) {
            $index_json = \wp_json_encode( $index_data );
        } else {
            $index_json = \json_encode( $index_data );
        }

        if ( false === $index_json ) {
            $this->logger->error( 'Failed to encode PDF index to JSON.' );
            return false;
        }

        $json_size = strlen( $index_json );
        $this->logger->debug( "JSON data size: {$json_size} bytes (" . round( $json_size / 1024, 2 ) . ' KB)' );

        // If WordPress functions aren't available, always use file storage
        if ( ! function_exists( 'update_option' ) ) {
            $this->logger->debug( 'WordPress functions not available, using file storage.' );
            return $this->save_index_to_file( $index_data );
        }

        // Update index version
        if ( function_exists( 'update_option' ) ) {
            \update_option( self::INDEX_VERSION_OPTION_NAME, self::CURRENT_INDEX_VERSION, 'no' );
        }

        // Check if the JSON is too large (WordPress typically has issues with options > 1MB)
        if ( $json_size > 1000000 ) { // 1MB limit
            return $this->save_compressed_index( $index_json, $index_data );
        }

        return $this->save_uncompressed_index( $index_json, $index_data );
    }

    /**
     * Clear PDF index from cache.
     *
     * @return bool True on success, false on failure.
     */
    public function clear_index(): bool {
        $result = true;

        // Clear WordPress options if available
        if ( function_exists( 'delete_option' ) ) {
            $result = \delete_option( self::INDEX_OPTION_NAME );
            \delete_option( self::INDEX_OPTION_NAME . '_compressed' );
            \delete_option( self::INDEX_OPTION_NAME . '_file_storage' );
        }

        // Clear file storage
        $file_path = $this->get_index_file_path();
        if ( file_exists( $file_path ) ) {
            $file_result = unlink( $file_path );
            $result = $result && $file_result;
        }

        $this->logger->info( 'PDF index cleared from cache.' );
        return $result;
    }

    /**
     * Decompress index data.
     *
     * @param string $compressed_data Base64 encoded compressed data.
     * @return array|null Decompressed index data or null on failure.
     */
    private function decompress_index( string $compressed_data ): ?array {
        $this->logger->debug( 'Loading compressed PDF index.' );
        
        // Decode base64 and decompress
        $decoded_data = base64_decode( $compressed_data );
        if ( false === $decoded_data ) {
            $this->logger->error( 'Failed to base64 decode compressed index.' );
            return null;
        }
        
        $index_json = gzuncompress( $decoded_data );
        if ( false === $index_json ) {
            $this->logger->error( 'Failed to decompress PDF index.' );
            return null;
        }
        
        $this->logger->debug( 'Successfully decompressed PDF index.' );
        return $this->decode_index( $index_json );
    }

    /**
     * Decode JSON index data.
     *
     * @param string $index_json JSON encoded index data.
     * @return array|null Decoded index data or null on failure.
     */
    private function decode_index( string $index_json ): ?array {
        $index_data = json_decode( $index_json, true );

        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $index_data ) ) {
            $this->logger->error( 'Error decoding PDF index JSON - ' . json_last_error_msg() );
            return null;
        }

        return $index_data;
    }

    /**
     * Save compressed index data.
     *
     * @param string $index_json  JSON encoded index data.
     * @param array  $index_data  Original index data for file fallback.
     * @return bool True on success, false on failure.
     */
    private function save_compressed_index( string $index_json, array $index_data ): bool {
        $this->logger->debug( 'JSON data too large, attempting to compress.' );

        $compressed_data = gzcompress( $index_json, 6 );
        if ( false === $compressed_data ) {
            $this->logger->error( 'Failed to compress PDF index data.' );
            return $this->save_index_to_file( $index_data );
        }

        $compressed_size = strlen( $compressed_data );
        $this->logger->debug( "Compressed size: {$compressed_size} bytes (" . round( $compressed_size / 1024, 2 ) . ' KB)' );

        // Try to save to WordPress options if available
        if ( function_exists( 'update_option' ) ) {
            $result = \update_option( self::INDEX_OPTION_NAME, base64_encode( $compressed_data ), 'no' );
            if ( $result ) {
                \update_option( self::INDEX_OPTION_NAME . '_compressed', true, 'no' );
                $this->logger->info( 'Successfully saved compressed PDF index.' );
                return true;
            }
            $this->logger->error( 'Failed to save compressed PDF index to database.' );
        }

        // Fallback to file storage
        return $this->save_index_to_file( $index_data );
    }

    /**
     * Save uncompressed index data.
     *
     * @param string $index_json  JSON encoded index data.
     * @param array  $index_data  Original index data for file fallback.
     * @return bool True on success, false on failure.
     */
    private function save_uncompressed_index( string $index_json, array $index_data ): bool {
        // Try to save to WordPress options if available
        if ( function_exists( 'update_option' ) ) {
            $result = \update_option( self::INDEX_OPTION_NAME, $index_json, 'no' );
            if ( $result ) {
                // Remove compression flag if it exists
                if ( function_exists( 'delete_option' ) ) {
                    \delete_option( self::INDEX_OPTION_NAME . '_compressed' );
                }
                $this->logger->info( 'Successfully saved PDF index.' );
                return true;
            }
            $this->logger->error( 'Failed to save PDF index to database.' );
        }

        // Fallback to file storage
        return $this->save_index_to_file( $index_data );
    }

    /**
     * Save index to file as fallback.
     *
     * @param array $index_data Index data to save.
     * @return bool True on success, false on failure.
     */
    private function save_index_to_file( array $index_data ): bool {
        $this->logger->debug( 'Attempting file-based storage as fallback.' );

        // Use WordPress JSON encoding if available, otherwise use PHP's json_encode
        if ( function_exists( 'wp_json_encode' ) ) {
            $json_data = \wp_json_encode( $index_data );
        } else {
            $json_data = \json_encode( $index_data );
        }

        if ( false === $json_data ) {
            $this->logger->error( 'Failed to encode index data for file storage.' );
            return false;
        }

        $file_path = $this->get_index_file_path();
        $bytes_written = file_put_contents( $file_path, $json_data, LOCK_EX );
        if ( false === $bytes_written ) {
            $this->logger->error( "Failed to write index file to: {$file_path}" );
            return false;
        }

        $this->logger->info( "Wrote {$bytes_written} bytes to index file." );

        // Set file storage flag if WordPress functions are available
        if ( function_exists( 'update_option' ) ) {
            \update_option( self::INDEX_OPTION_NAME . '_file_storage', true, 'no' );
        }

        return true;
    }

    /**
     * Load index from file.
     *
     * @return array|null Index data or null on failure.
     */
    private function load_index_from_file(): ?array {
        $file_path = $this->get_index_file_path();
        
        if ( ! file_exists( $file_path ) || ! is_readable( $file_path ) ) {
            $this->logger->debug( "Index file does not exist or is not readable: {$file_path}" );
            return null;
        }
        
        $json_data = file_get_contents( $file_path );
        if ( false === $json_data ) {
            $this->logger->error( "Failed to read index file: {$file_path}" );
            return null;
        }
        
        $index_data = json_decode( $json_data, true );
        if ( JSON_ERROR_NONE !== json_last_error() || ! is_array( $index_data ) ) {
            $this->logger->error( 'Failed to decode JSON from index file: ' . json_last_error_msg() );
            return null;
        }
        
        $this->logger->debug( 'Successfully loaded ' . count( $index_data ) . ' items from index file.' );
        return $index_data;
    }

    /**
     * Get index file path.
     *
     * @return string File path for index storage.
     */
    private function get_index_file_path(): string {
        return $this->plugin_dir . self::INDEX_FILE_PATH;
    }
}
