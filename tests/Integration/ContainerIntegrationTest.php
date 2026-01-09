<?php
/**
 * Container Integration Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Integration;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Core\Container;
use KissPlugins\AutomatedPdfLinker\Providers\CoreServiceProvider;
use KissPlugins\AutomatedPdfLinker\Providers\ServiceLayerProvider;
use KissPlugins\AutomatedPdfLinker\Providers\AdminServiceProvider;
use KissPlugins\AutomatedPdfLinker\Providers\FrontendServiceProvider;

/**
 * Container Integration Test Class
 *
 * Tests the integration of the DI container with service providers.
 *
 * @since 3.0.0
 */
class ContainerIntegrationTest extends TestCase {

    /**
     * Container instance.
     *
     * @var Container
     */
    private $container;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->container = new Container();
        
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
    }

    /**
     * Test core service provider integration.
     *
     * @return void
     */
    public function test_core_service_provider_integration(): void {
        // Test logger service
        $logger1 = $this->container->get( 'logger' );
        $logger2 = $this->container->get( 'KissPlugins\AutomatedPdfLinker\Utils\Logger' );
        $this->assertSame( $logger1, $logger2, 'Logger aliases should resolve to same instance' );
        
        // Test file normalizer
        $normalizer = $this->container->get( 'file_normalizer' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Utils\FileNormalizer', 
            $normalizer 
        );
        
        // Test parameters
        $this->assertTrue( $this->container->has( 'plugin_version' ) );
    }

    /**
     * Test service layer provider integration.
     *
     * @return void
     */
    public function test_service_layer_provider_integration(): void {
        // Test cache manager (singleton)
        $cache1 = $this->container->get( 'cache_manager' );
        $cache2 = $this->container->get( 'cache_manager' );
        $this->assertSame( $cache1, $cache2, 'Cache manager should be singleton' );
        
        // Test file scanner
        $scanner1 = $this->container->get( 'file_scanner' );
        $scanner2 = $this->container->get( 'file_scanner' );
        $this->assertNotSame( $scanner1, $scanner2, 'File scanner should not be singleton' );
        
        // Test fuzzy matcher
        $matcher = $this->container->get( 'fuzzy_matcher' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Services\FuzzyMatcher', 
            $matcher 
        );
        
        // Test index builder (singleton)
        $builder1 = $this->container->get( 'index_builder' );
        $builder2 = $this->container->get( 'index_builder' );
        $this->assertSame( $builder1, $builder2, 'Index builder should be singleton' );
    }

    /**
     * Test admin service provider integration.
     *
     * @return void
     */
    public function test_admin_service_provider_integration(): void {
        // Test settings (singleton)
        $settings1 = $this->container->get( 'settings' );
        $settings2 = $this->container->get( 'settings' );
        $this->assertSame( $settings1, $settings2, 'Settings should be singleton' );
        
        // Test admin menu
        $admin_menu = $this->container->get( 'admin_menu' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Admin\AdminMenu', 
            $admin_menu 
        );
    }

    /**
     * Test frontend service provider integration.
     *
     * @return void
     */
    public function test_frontend_service_provider_integration(): void {
        // Test shortcode (singleton)
        $shortcode1 = $this->container->get( 'shortcode' );
        $shortcode2 = $this->container->get( 'shortcode' );
        $this->assertSame( $shortcode1, $shortcode2, 'Shortcode should be singleton' );
    }

    /**
     * Test dependency injection between services.
     *
     * @return void
     */
    public function test_dependency_injection_between_services(): void {
        // Test that cache manager receives logger dependency
        $cache_manager = $this->container->get( 'cache_manager' );
        $logger = $this->container->get( 'logger' );
        
        // We can't directly access private properties, but we can test functionality
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Services\CacheManager', 
            $cache_manager 
        );
        
        // Test that index builder receives both cache manager and logger
        $index_builder = $this->container->get( 'index_builder' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Services\IndexBuilder', 
            $index_builder 
        );
    }

    /**
     * Test automatic dependency resolution.
     *
     * @return void
     */
    public function test_automatic_dependency_resolution(): void {
        // Test that container can automatically resolve class dependencies
        $logger_class = 'KissPlugins\AutomatedPdfLinker\Utils\Logger';
        $logger = $this->container->get( $logger_class );
        
        $this->assertInstanceOf( $logger_class, $logger );
        
        // Test that it returns the same instance as the registered service
        $registered_logger = $this->container->get( 'logger' );
        $this->assertSame( $logger, $registered_logger );
    }

    /**
     * Test service resolution with complex dependencies.
     *
     * @return void
     */
    public function test_service_resolution_with_complex_dependencies(): void {
        // Test settings service which depends on index builder and logger
        $settings = $this->container->get( 'settings' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Admin\Settings', 
            $settings 
        );
        
        // Test that the dependencies are properly injected by testing functionality
        // (We can't access private properties directly)
        $this->assertTrue( method_exists( $settings, 'register_settings' ) );
    }

    /**
     * Test container method calling with dependency injection.
     *
     * @return void
     */
    public function test_container_method_calling_with_dependency_injection(): void {
        // Create a test callable that requires dependencies
        $test_callable = function( $logger, $cache_manager ) {
            return [
                'logger_class' => get_class( $logger ),
                'cache_manager_class' => get_class( $cache_manager )
            ];
        };
        
        $result = $this->container->call( $test_callable );
        
        $this->assertIsArray( $result );
        $this->assertStringContains( 'Logger', $result['logger_class'] );
        $this->assertStringContains( 'CacheManager', $result['cache_manager_class'] );
    }

    /**
     * Test service provider boot methods.
     *
     * @return void
     */
    public function test_service_provider_boot_methods(): void {
        // Create a new container to test boot process
        $test_container = new Container();
        
        $core_provider = new CoreServiceProvider( $test_container );
        $core_provider->register();
        $core_provider->boot();
        
        // Test that services are still available after boot
        $this->assertTrue( $test_container->has( 'logger' ) );
        $this->assertTrue( $test_container->has( 'file_normalizer' ) );
    }

    /**
     * Test container performance with many services.
     *
     * @return void
     */
    public function test_container_performance_with_many_services(): void {
        $start_time = microtime( true );
        
        // Resolve multiple services multiple times
        for ( $i = 0; $i < 100; $i++ ) {
            $this->container->get( 'logger' );
            $this->container->get( 'cache_manager' );
            $this->container->get( 'index_builder' );
            $this->container->get( 'settings' );
        }
        
        $end_time = microtime( true );
        $execution_time = $end_time - $start_time;
        
        // Should complete in reasonable time (less than 1 second)
        $this->assertLessThan( 1.0, $execution_time, 'Container resolution should be fast' );
    }

    /**
     * Test container memory usage.
     *
     * @return void
     */
    public function test_container_memory_usage(): void {
        $initial_memory = memory_get_usage();
        
        // Create many service instances
        for ( $i = 0; $i < 50; $i++ ) {
            $this->container->get( 'file_scanner' ); // Non-singleton service
        }
        
        $final_memory = memory_get_usage();
        $memory_increase = $final_memory - $initial_memory;
        
        // Memory increase should be reasonable (less than 10MB)
        $this->assertLessThan( 10 * 1024 * 1024, $memory_increase, 'Memory usage should be reasonable' );
    }

    /**
     * Test container clearing functionality.
     *
     * @return void
     */
    public function test_container_clearing_functionality(): void {
        // Verify services exist
        $this->assertTrue( $this->container->has( 'logger' ) );
        $this->assertNotEmpty( $this->container->getServices() );
        
        // Clear container
        $this->container->clear();
        
        // Verify services are cleared
        $this->assertEmpty( $this->container->getServices() );
        $this->assertEmpty( $this->container->getInstances() );
        $this->assertFalse( $this->container->has( 'logger' ) );
    }
}
