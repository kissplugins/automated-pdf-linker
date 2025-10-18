<?php
/**
 * PHPUnit Bootstrap File
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

// Define test environment
define( 'KAPL_TESTING', true );

// Include the autoloader
require_once __DIR__ . '/../src/autoloader.php';

// Mock WordPress functions for testing
if ( ! function_exists( 'plugin_dir_path' ) ) {
    function plugin_dir_path( $file ) {
        return dirname( $file ) . '/';
    }
}

if ( ! function_exists( 'plugin_dir_url' ) ) {
    function plugin_dir_url( $file ) {
        return 'http://example.com/wp-content/plugins/' . basename( dirname( $file ) ) . '/';
    }
}

if ( ! function_exists( 'get_option' ) ) {
    function get_option( $option, $default = false ) {
        static $options = [];
        return isset( $options[ $option ] ) ? $options[ $option ] : $default;
    }
}

if ( ! function_exists( 'update_option' ) ) {
    function update_option( $option, $value, $autoload = null ) {
        static $options = [];
        $options[ $option ] = $value;
        return true;
    }
}

if ( ! function_exists( 'delete_option' ) ) {
    function delete_option( $option ) {
        static $options = [];
        unset( $options[ $option ] );
        return true;
    }
}

if ( ! function_exists( 'wp_json_encode' ) ) {
    function wp_json_encode( $data, $options = 0, $depth = 512 ) {
        return json_encode( $data, $options, $depth );
    }
}

if ( ! function_exists( 'esc_html' ) ) {
    function esc_html( $text ) {
        return htmlspecialchars( $text, ENT_QUOTES, 'UTF-8' );
    }
}

if ( ! function_exists( 'esc_html__' ) ) {
    function esc_html__( $text, $domain = 'default' ) {
        return esc_html( $text );
    }
}

if ( ! function_exists( 'esc_url' ) ) {
    function esc_url( $url ) {
        return filter_var( $url, FILTER_SANITIZE_URL );
    }
}

if ( ! function_exists( 'admin_url' ) ) {
    function admin_url( $path = '', $scheme = 'admin' ) {
        return 'http://example.com/wp-admin/' . ltrim( $path, '/' );
    }
}

if ( ! function_exists( 'add_action' ) ) {
    function add_action( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'add_filter' ) ) {
    function add_filter( $hook, $callback, $priority = 10, $accepted_args = 1 ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'add_shortcode' ) ) {
    function add_shortcode( $tag, $callback ) {
        // Mock implementation for testing
        return true;
    }
}

if ( ! function_exists( 'plugin_basename' ) ) {
    function plugin_basename( $file ) {
        return basename( dirname( $file ) ) . '/' . basename( $file );
    }
}

// Define WordPress constants for testing
if ( ! defined( 'ABSPATH' ) ) {
    define( 'ABSPATH', '/tmp/wordpress/' );
}

if ( ! defined( 'WP_CONTENT_DIR' ) ) {
    define( 'WP_CONTENT_DIR', ABSPATH . 'wp-content' );
}

if ( ! defined( 'WP_PLUGIN_DIR' ) ) {
    define( 'WP_PLUGIN_DIR', WP_CONTENT_DIR . '/plugins' );
}

// Set plugin directory constant for testing
if ( ! defined( 'KAPL_PLUGIN_DIR' ) ) {
    define( 'KAPL_PLUGIN_DIR', __DIR__ . '/../' );
}

echo "PHPUnit Bootstrap: Test environment initialized\n";
