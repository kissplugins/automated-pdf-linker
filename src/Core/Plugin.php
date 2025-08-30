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
use KissPlugins\AutomatedPdfLinker\Admin\SelfTestPage;
use KissPlugins\AutomatedPdfLinker\Admin\Assets as AdminAssets;
use KissPlugins\AutomatedPdfLinker\Frontend\Shortcode;
use KissPlugins\AutomatedPdfLinker\Frontend\Assets as FrontendAssets;
use KissPlugins\AutomatedPdfLinker\Services\IndexBuilder;
use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;
use KissPlugins\AutomatedPdfLinker\Providers\CoreServiceProvider;
use KissPlugins\AutomatedPdfLinker\Providers\ServiceLayerProvider;
use KissPlugins\AutomatedPdfLinker\Providers\AdminServiceProvider;
use KissPlugins\AutomatedPdfLinker\Providers\FrontendServiceProvider;

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
     * Dependency injection container.
     *
     * @var Container
     */
    private $container;

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
     * Self-test page instance.
     *
     * @var SelfTestPage
     */
    private $self_test_page;

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

        // Set plugin directory and URL with fallbacks
        if ( function_exists( 'plugin_dir_path' ) ) {
            $this->plugin_dir = \plugin_dir_path( $plugin_file );
        } else {
            $this->plugin_dir = dirname( $plugin_file ) . '/';
        }

        if ( function_exists( 'plugin_dir_url' ) ) {
            $this->plugin_url = \plugin_dir_url( $plugin_file );
        } else {
            $this->plugin_url = '';
        }

        $this->init_container();
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
     * Initialize dependency injection container.
     *
     * @return void
     */
    private function init_container(): void {
        try {
            $this->container = new Container();

            // Register the plugin instance in the container
            $this->container->instance( 'plugin', $this );
            $this->container->instance( Plugin::class, $this );

            // Register service providers
            $providers = [
                new CoreServiceProvider( $this->container ),
                new ServiceLayerProvider( $this->container ),
                new AdminServiceProvider( $this->container ),
                new FrontendServiceProvider( $this->container ),
            ];

            // Register all services
            foreach ( $providers as $provider ) {
                $provider->register();
            }

            // Boot all services
            foreach ( $providers as $provider ) {
                $provider->boot();
            }
        } catch ( \Exception $e ) {
            // Log error and use fallback initialization
            error_log( 'KISS PDF Linker: Container initialization failed - ' . $e->getMessage() );
            $this->init_services_fallback();
        }
    }

    /**
     * Initialize services using the container.
     *
     * @return void
     */
    private function init_services(): void {
        try {
            // Resolve services from container
            $this->logger         = $this->container->get( Logger::class );
            $this->cache_manager  = $this->container->get( CacheManager::class );
            $this->index_builder  = $this->container->get( IndexBuilder::class );
            $this->settings       = $this->container->get( Settings::class );
            $this->admin_menu     = $this->container->get( AdminMenu::class );
            $this->self_test_page = $this->container->get( SelfTestPage::class );
            $this->shortcode      = $this->container->get( Shortcode::class );
        } catch ( \Exception $e ) {
            // Fallback to manual initialization
            error_log( 'KISS PDF Linker: Service resolution failed - ' . $e->getMessage() );
            $this->init_services_fallback();
        }
    }

    /**
     * Fallback service initialization without container.
     *
     * @return void
     */
    private function init_services_fallback(): void {
        $this->logger        = new Logger();
        $this->cache_manager = new CacheManager( $this->logger );
        $this->index_builder = new IndexBuilder( $this->cache_manager, $this->logger );
        $this->settings       = new Settings( $this->index_builder, $this->logger );
        $this->admin_menu     = new AdminMenu( $this->settings );
        $this->self_test_page = new SelfTestPage( $this );
        $this->shortcode      = new Shortcode( $this->cache_manager, $this->logger );
    }

    /**
     * Initialize WordPress hooks.
     *
     * @return void
     */
    private function init_hooks(): void {
        // Only register hooks if WordPress functions are available
        if ( ! function_exists( 'add_action' ) ) {
            return;
        }

        \add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        \add_action( 'admin_init', [ $this->settings, 'register_settings' ] );
        \add_action( 'admin_menu', [ $this->admin_menu, 'add_admin_menu' ] );
        \add_action( 'admin_menu', [ $this->self_test_page, 'add_admin_menu' ] );
        \add_action( 'wp_enqueue_scripts', [ FrontendAssets::class, 'enqueue_styles' ] );
        \add_action( 'admin_enqueue_scripts', [ AdminAssets::class, 'enqueue_scripts' ] );
        \add_shortcode( 'kiss_pdf', [ $this->shortcode, 'handle' ] );
        \add_filter( 'plugin_action_links_' . \plugin_basename( $this->plugin_file ), [ $this, 'add_settings_link' ] );
        \add_filter( 'woocommerce_product_tabs', [ $this->shortcode, 'customize_strain_tab' ], 98 );
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
            esc_url( admin_url( 'tools.php?page=kiss-pdf-linker-settings' ) ),
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

    /**
     * Get dependency injection container.
     *
     * @return Container
     */
    public function get_container(): Container {
        return $this->container;
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $id Service identifier.
     * @return mixed Resolved service.
     */
    public function resolve( string $id ) {
        return $this->container->get( $id );
    }
}
