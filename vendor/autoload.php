<?php
/**
 * Simple PSR-4 Autoloader for KISS Automated PDF Linker
 *
 * This is a basic autoloader implementation for development purposes.
 * In production, use Composer's autoloader.
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

spl_autoload_register( function ( $class ) {
    // Project-specific namespace prefix
    $prefix = 'KissPlugins\\AutomatedPdfLinker\\';

    // Base directory for the namespace prefix
    $base_dir = __DIR__ . '/../src/';

    // Does the class use the namespace prefix?
    $len = strlen( $prefix );
    if ( strncmp( $prefix, $class, $len ) !== 0 ) {
        // No, move to the next registered autoloader
        return;
    }

    // Get the relative class name
    $relative_class = substr( $class, $len );

    // Replace the namespace prefix with the base directory, replace namespace
    // separators with directory separators in the relative class name, append
    // with .php
    $file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

    // If the file exists, require it
    if ( file_exists( $file ) ) {
        require $file;
    }
} );
