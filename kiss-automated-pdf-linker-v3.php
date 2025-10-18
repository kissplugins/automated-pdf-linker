<?php
/**
 * Plugin Name:       KISS Automated PDF Linker (PSR4)
 * Plugin URI:        https://github.com/kissplugins/KISS-automated-pdf-linker
 * Description:       Scans selected upload directories for PDF files and provides a shortcode [kiss_pdf name="filename"] to link to them using fuzzy matching.
 * Version:           3.1.9
 * Requires at least: 5.2
 * Requires PHP:      7.4
 * Author:            KISS / Neochrome, Inc.
 * Author URI:        https://KISSplugins.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       kiss-automated-pdf-linker
 * Domain Path:       /languages
 * GitHub Plugin URI: https://github.com/kissplugins/KISS-automated-pdf-linker
 * GitHub Branch: main
 */

// Exit if accessed directly to prevent direct execution.
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check for version conflicts
if ( defined( 'KAPL_VERSION' ) && version_compare( KAPL_VERSION, '3.0.0', '<' ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>KISS Automated PDF Linker:</strong> ';
        echo 'Version conflict detected! Please deactivate the old version (v' . KAPL_VERSION . ') before using v3.0.0.';
        echo '</p></div>';
    } );
    return; // Stop loading v3
}

// Include PSR-4 autoloader
$autoloader_path = plugin_dir_path( __FILE__ ) . 'src/autoloader.php';
if ( ! file_exists( $autoloader_path ) ) {
    add_action( 'admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>KISS Automated PDF Linker:</strong> ';
        echo 'Autoloader file missing. Please reinstall the plugin.';
        echo '</p></div>';
    } );
    return;
}
require_once $autoloader_path;

// Include the Plugin Update Checker
$update_checker_path = plugin_dir_path( __FILE__ ) . 'lib/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $update_checker_path ) ) {
    require $update_checker_path;

    if ( class_exists( 'YahnisElsts\PluginUpdateChecker\v5\PucFactory' ) ) {
        $myUpdateChecker = \YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
            'https://github.com/kissplugins/automated-pdf-linker',
            __FILE__,
            'kiss-automated-pdf-linker'
        );
        $myUpdateChecker->setBranch( 'main' );
    }
}

// Use the new namespaced classes
use KissPlugins\AutomatedPdfLinker\Core\Plugin;
use KissPlugins\AutomatedPdfLinker\Core\Activator;
use KissPlugins\AutomatedPdfLinker\Core\Deactivator;

/**
 * Initialize the plugin.
 *
 * @since 3.0.0
 */
function kapl_init_plugin(): void {
    Plugin::get_instance( __FILE__ );
}

// Initialize plugin after WordPress is loaded
add_action( 'plugins_loaded', 'kapl_init_plugin' );

/**
 * Plugin activation hook.
 *
 * @since 3.0.0
 */
function kapl_activate_plugin(): void {
    Activator::activate();
}
register_activation_hook( __FILE__, 'kapl_activate_plugin' );

/**
 * Plugin deactivation hook.
 *
 * @since 3.0.0
 */
function kapl_deactivate_plugin(): void {
    Deactivator::deactivate();
}
register_deactivation_hook( __FILE__, 'kapl_deactivate_plugin' );

// Backward compatibility constants for any external code that might reference them
if ( ! defined( 'KAPL_VERSION' ) ) {
    define( 'KAPL_VERSION', '3.1.9' );
}
if ( ! defined( 'KAPL_PLUGIN_DIR' ) ) {
    define( 'KAPL_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}
if ( ! defined( 'KAPL_PLUGIN_URL' ) ) {
    define( 'KAPL_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'KAPL_SETTINGS_OPTION_NAME' ) ) {
    define( 'KAPL_SETTINGS_OPTION_NAME', 'kapl_settings' );
}
if ( ! defined( 'KAPL_INDEX_OPTION_NAME' ) ) {
    define( 'KAPL_INDEX_OPTION_NAME', 'kapl_pdf_index' );
}
if ( ! defined( 'KAPL_SIMILARITY_THRESHOLD' ) ) {
    define( 'KAPL_SIMILARITY_THRESHOLD', 50 );
}
if ( ! defined( 'KAPL_SHORTCODE_TAG' ) ) {
    define( 'KAPL_SHORTCODE_TAG', 'kiss_pdf' );
}
if ( ! defined( 'KAPL_SETTINGS_SLUG' ) ) {
    define( 'KAPL_SETTINGS_SLUG', 'kiss-pdf-linker-settings' );
}
if ( ! defined( 'KAPL_INDEX_FILE_PATH' ) ) {
    define( 'KAPL_INDEX_FILE_PATH', KAPL_PLUGIN_DIR . 'pdf-index.json' );
}
