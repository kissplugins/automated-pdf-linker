<?php
/**
 * Main Plugin Class
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Core;

use KissPlugins\AutomatedPdfLinker\Admin\Settings;
use KissPlugins\AutomatedPdfLinker\Admin\AdminMenu;
use KissPlugins\AutomatedPdfLinker\Admin\Assets as AdminAssets;
use KissPlugins\AutomatedPdfLinker\Frontend\Shortcode;
use KissPlugins\AutomatedPdfLinker\Frontend\Assets as FrontendAssets;
use KissPlugins\AutomatedPdfLinker\Services\IndexBuilder;
use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Main Plugin Class
 *
 * Coordinates all plugin functionality and manages the plugin lifecycle.
 *
 * @since 3.0.0
 */
class Plugin {

    /**
     * Plugin version.
     *
     * @var string
     */
    const VERSION = '3.0.0';

    /**
     * Plugin instance.
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Plugin file path.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Plugin directory path.
     *
     * @var string
     */
    private $plugin_dir;

    /**
     * Plugin directory URL.
     *
     * @var string
     */
    private $plugin_url;

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Admin menu instance.
     *
     * @var AdminMenu
     */
    private $admin_menu;

    /**
     * Shortcode handler instance.
     *
     * @var Shortcode
     */
    private $shortcode;

    /**
     * Index builder instance.
     *
     * @var IndexBuilder
     */
    private $index_builder;

    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private $cache_manager;

    /**
     * Logger instance.
     *
     * @var Logger
     */
    private $logger;

    /**
     * Constructor.
     *
     * @param string $plugin_file Main plugin file path.
     */
    private function __construct( string $plugin_file ) {
        $this->plugin_file = $plugin_file;
        $this->plugin_dir  = plugin_dir_path( $plugin_file );
        $this->plugin_url  = plugin_dir_url( $plugin_file );

        $this->init_services();
        $this->init_hooks();
    }

    /**
     * Get plugin instance.
     *
     * @param string|null $plugin_file Main plugin file path.
     * @return Plugin
     */
    public static function get_instance( string $plugin_file = null ): Plugin {
        if ( null === self::$instance ) {
            if ( null === $plugin_file ) {
                throw new \InvalidArgumentException( 'Plugin file path is required for first instantiation.' );
            }
            self::$instance = new self( $plugin_file );
        }

        return self::$instance;
    }

    /**
     * Initialize services.
     *
     * @return void
     */
    private function init_services(): void {
        $this->logger        = new Logger();
        $this->cache_manager = new CacheManager( $this->logger );
        $this->index_builder = new IndexBuilder( $this->cache_manager, $this->logger );
        $this->settings      = new Settings( $this->index_builder, $this->logger );
        $this->admin_menu    = new AdminMenu( $this->settings );
        $this->shortcode     = new Shortcode( $this->cache_manager, $this->logger );
    }

    /**
     * Initialize WordPress hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_action( 'admin_init', [ $this->settings, 'register_settings' ] );
        add_action( 'admin_menu', [ $this->admin_menu, 'add_admin_menu' ] );
        add_action( 'wp_enqueue_scripts', [ FrontendAssets::class, 'enqueue_styles' ] );
        add_action( 'admin_enqueue_scripts', [ AdminAssets::class, 'enqueue_scripts' ] );
        add_shortcode( 'kiss_pdf', [ $this->shortcode, 'handle' ] );
        add_filter( 'plugin_action_links_' . plugin_basename( $this->plugin_file ), [ $this, 'add_settings_link' ] );
        add_filter( 'woocommerce_product_tabs', [ $this->shortcode, 'customize_strain_tab' ], 98 );
    }

    /**
     * Load plugin textdomain for translations.
     *
     * @return void
     */
    public function load_textdomain(): void {
        load_plugin_textdomain(
            'kiss-automated-pdf-linker',
            false,
            dirname( plugin_basename( $this->plugin_file ) ) . '/languages'
        );
    }

    /**
     * Add settings link to plugin actions.
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_settings_link( array $links ): array {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url( admin_url( 'options-general.php?page=kiss-pdf-linker-settings' ) ),
            esc_html__( 'Settings', 'kiss-automated-pdf-linker' )
        );

        array_unshift( $links, $settings_link );

        return $links;
    }

    /**
     * Get plugin version.
     *
     * @return string
     */
    public function get_version(): string {
        return self::VERSION;
    }

    /**
     * Get plugin directory path.
     *
     * @return string
     */
    public function get_plugin_dir(): string {
        return $this->plugin_dir;
    }

    /**
     * Get plugin directory URL.
     *
     * @return string
     */
    public function get_plugin_url(): string {
        return $this->plugin_url;
    }

    /**
     * Get settings instance.
     *
     * @return Settings
     */
    public function get_settings(): Settings {
        return $this->settings;
    }

    /**
     * Get index builder instance.
     *
     * @return IndexBuilder
     */
    public function get_index_builder(): IndexBuilder {
        return $this->index_builder;
    }

    /**
     * Get cache manager instance.
     *
     * @return CacheManager
     */
    public function get_cache_manager(): CacheManager {
        return $this->cache_manager;
    }

    /**
     * Get logger instance.
     *
     * @return Logger
     */
    public function get_logger(): Logger {
        return $this->logger;
    }
}
