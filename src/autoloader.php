<?php
/**
 * PSR-4 Autoloader for KISS Automated PDF Linker
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * PSR-4 Autoloader Class
 *
 * @since 3.0.0
 */
class KaplAutoloader {

    /**
     * Namespace prefix to directory mapping.
     *
     * @var array
     */
    private $prefixes = [];

    /**
     * Register the autoloader.
     *
     * @return void
     */
    public function register(): void {
        \spl_autoload_register( [ $this, 'loadClass' ] );
    }

    /**
     * Add a namespace prefix and directory mapping.
     *
     * @param string $prefix    Namespace prefix.
     * @param string $base_dir  Base directory for the namespace.
     * @param bool   $prepend   Whether to prepend to the stack.
     * @return void
     */
    public function addNamespace( string $prefix, string $base_dir, bool $prepend = false ): void {
        // Normalize namespace prefix
        $prefix = \trim( $prefix, '\\' ) . '\\';

        // Normalize base directory with trailing separator
        $base_dir = \rtrim( $base_dir, DIRECTORY_SEPARATOR ) . '/';

        // Initialize the namespace prefix array
        if ( ! isset( $this->prefixes[ $prefix ] ) ) {
            $this->prefixes[ $prefix ] = [];
        }

        // Retain the base directory for the namespace prefix
        if ( $prepend ) {
            \array_unshift( $this->prefixes[ $prefix ], $base_dir );
        } else {
            \array_push( $this->prefixes[ $prefix ], $base_dir );
        }
    }

    /**
     * Load the class file for a given class name.
     *
     * @param string $class Fully qualified class name.
     * @return mixed The mapped file name on success, or boolean false on failure.
     */
    public function loadClass( string $class ) {
        // The current namespace prefix
        $prefix = $class;

        // Work backwards through the namespace names of the fully-qualified
        // class name to find a mapped file name
        while ( false !== $pos = \strrpos( $prefix, '\\' ) ) {

            // Retain the trailing namespace separator in the prefix
            $prefix = \substr( $class, 0, $pos + 1 );

            // The rest is the relative class name
            $relative_class = \substr( $class, $pos + 1 );

            // Try to load a mapped file for the prefix and relative class
            $mapped_file = $this->loadMappedFile( $prefix, $relative_class );
            if ( $mapped_file ) {
                return $mapped_file;
            }

            // Remove the trailing namespace separator for the next iteration
            // of strrpos()
            $prefix = \rtrim( $prefix, '\\' );
        }

        // Never found a mapped file
        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix         Namespace prefix.
     * @param string $relative_class Relative class name.
     * @return mixed Boolean false if no mapped file can be loaded, or the
     *               name of the mapped file that was loaded.
     */
    protected function loadMappedFile( string $prefix, string $relative_class ) {
        // Are there any base directories for this namespace prefix?
        if ( ! isset( $this->prefixes[ $prefix ] ) ) {
            return false;
        }

        // Look through base directories for this namespace prefix
        foreach ( $this->prefixes[ $prefix ] as $base_dir ) {

            // Replace the namespace prefix with the base directory,
            // replace namespace separators with directory separators
            // in the relative class name, append with .php
            $file = $base_dir
                  . \str_replace( '\\', '/', $relative_class )
                  . '.php';

            // If the mapped file exists, require it
            if ( $this->requireFile( $file ) ) {
                // Yes, we're done
                return $file;
            }
        }

        // Never found it
        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file File path.
     * @return bool True if the file exists and was required, false otherwise.
     */
    protected function requireFile( string $file ): bool {
        if ( \file_exists( $file ) ) {
            require $file;
            return true;
        }
        return false;
    }
}

// Initialize and register the autoloader
$autoloader = new KaplAutoloader();
$autoloader->addNamespace( 'KissPlugins\\AutomatedPdfLinker\\', __DIR__ );
$autoloader->register();
