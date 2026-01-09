<?php
/**
 * Plugin Integration Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Integration;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Core\Plugin;
use KissPlugins\AutomatedPdfLinker\Core\Container;

/**
 * Plugin Integration Test Class
 *
 * Tests the integration between different components of the plugin.
 *
 * @since 3.0.0
 */
class PluginIntegrationTest extends TestCase {

    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Test plugin file path.
     *
     * @var string
     */
    private $plugin_file;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        // Mock plugin file path
        $this->plugin_file = __DIR__ . '/../../kiss-automated-pdf-linker-v3.php';
        
        // Get plugin instance
        $this->plugin = Plugin::get_instance( $this->plugin_file );
    }

    /**
     * Test plugin singleton pattern.
     *
     * @return void
     */
    public function test_plugin_singleton_pattern(): void {
        $instance1 = Plugin::get_instance( $this->plugin_file );
        $instance2 = Plugin::get_instance( $this->plugin_file );
        
        $this->assertSame( $instance1, $instance2, 'Plugin should follow singleton pattern' );
    }

    /**
     * Test dependency injection container integration.
     *
     * @return void
     */
    public function test_dependency_injection_container_integration(): void {
        $container = $this->plugin->get_container();
        
        $this->assertInstanceOf( Container::class, $container );
        
        // Test that core services are registered
        $this->assertTrue( $container->has( 'logger' ) );
        $this->assertTrue( $container->has( 'cache_manager' ) );
        $this->assertTrue( $container->has( 'index_builder' ) );
        $this->assertTrue( $container->has( 'settings' ) );
    }

    /**
     * Test service resolution through container.
     *
     * @return void
     */
    public function test_service_resolution_through_container(): void {
        $container = $this->plugin->get_container();
        
        // Test logger resolution
        $logger1 = $container->get( 'logger' );
        $logger2 = $container->get( 'logger' );
        $this->assertSame( $logger1, $logger2, 'Logger should be singleton' );
        
        // Test cache manager resolution
        $cache_manager = $container->get( 'cache_manager' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Services\CacheManager', 
            $cache_manager 
        );
        
        // Test index builder resolution
        $index_builder = $container->get( 'index_builder' );
        $this->assertInstanceOf( 
            'KissPlugins\AutomatedPdfLinker\Services\IndexBuilder', 
            $index_builder 
        );
    }

    /**
     * Test service dependencies are properly injected.
     *
     * @return void
     */
    public function test_service_dependencies_properly_injected(): void {
        $cache_manager = $this->plugin->get_cache_manager();
        $index_builder = $this->plugin->get_index_builder();
        
        // Test that services are properly instantiated
        $this->assertNotNull( $cache_manager );
        $this->assertNotNull( $index_builder );
        
        // Test that dependencies are working by performing operations
        $test_data = [
            'integration-test.pdf' => [
                'path' => '/test/integration-test.pdf',
                'filename' => 'integration-test.pdf',
                'normalized_name' => 'integration-test',
                'size' => 1024
            ]
        ];
        
        // Test cache manager can store and retrieve data
        $store_result = $cache_manager->update_index( $test_data );
        $this->assertTrue( $store_result );
        
        $retrieved_data = $cache_manager->get_index();
        $this->assertNotNull( $retrieved_data );
        $this->assertArrayHasKey( 'integration-test.pdf', $retrieved_data );
        
        // Clean up
        $cache_manager->clear_index();
    }

    /**
     * Test plugin service getters.
     *
     * @return void
     */
    public function test_plugin_service_getters(): void {
        // Test all service getters return valid instances
        $this->assertNotNull( $this->plugin->get_logger() );
        $this->assertNotNull( $this->plugin->get_cache_manager() );
        $this->assertNotNull( $this->plugin->get_index_builder() );
        $this->assertNotNull( $this->plugin->get_settings() );
        $this->assertNotNull( $this->plugin->get_admin_menu() );
        $this->assertNotNull( $this->plugin->get_self_test_page() );
        $this->assertNotNull( $this->plugin->get_shortcode() );
    }

    /**
     * Test container service resolution consistency.
     *
     * @return void
     */
    public function test_container_service_resolution_consistency(): void {
        $container = $this->plugin->get_container();
        
        // Test that plugin getters return same instances as container
        $logger_from_plugin = $this->plugin->get_logger();
        $logger_from_container = $container->get( 'logger' );
        $this->assertSame( $logger_from_plugin, $logger_from_container );
        
        $cache_manager_from_plugin = $this->plugin->get_cache_manager();
        $cache_manager_from_container = $container->get( 'cache_manager' );
        $this->assertSame( $cache_manager_from_plugin, $cache_manager_from_container );
    }

    /**
     * Test service provider registration.
     *
     * @return void
     */
    public function test_service_provider_registration(): void {
        $container = $this->plugin->get_container();
        
        // Test that services from different providers are available
        
        // Core services
        $this->assertTrue( $container->has( 'logger' ) );
        $this->assertTrue( $container->has( 'file_normalizer' ) );
        
        // Service layer services
        $this->assertTrue( $container->has( 'cache_manager' ) );
        $this->assertTrue( $container->has( 'file_scanner' ) );
        $this->assertTrue( $container->has( 'fuzzy_matcher' ) );
        $this->assertTrue( $container->has( 'index_builder' ) );
        
        // Admin services
        $this->assertTrue( $container->has( 'settings' ) );
        $this->assertTrue( $container->has( 'admin_menu' ) );
        
        // Frontend services
        $this->assertTrue( $container->has( 'shortcode' ) );
    }

    /**
     * Test plugin resolve method.
     *
     * @return void
     */
    public function test_plugin_resolve_method(): void {
        // Test that plugin resolve method works
        $logger = $this->plugin->resolve( 'logger' );
        $this->assertNotNull( $logger );
        
        $cache_manager = $this->plugin->resolve( 'cache_manager' );
        $this->assertNotNull( $cache_manager );
        
        // Test class name resolution
        $logger_by_class = $this->plugin->resolve( 'KissPlugins\AutomatedPdfLinker\Utils\Logger' );
        $this->assertSame( $logger, $logger_by_class );
    }

    /**
     * Test error handling in service resolution.
     *
     * @return void
     */
    public function test_error_handling_in_service_resolution(): void {
        $container = $this->plugin->get_container();
        
        // Test that non-existent service throws exception
        $this->expectException( \InvalidArgumentException::class );
        $container->get( 'non_existent_service' );
    }

    /**
     * Test plugin initialization without WordPress functions.
     *
     * @return void
     */
    public function test_plugin_initialization_without_wordpress_functions(): void {
        // This test verifies that the plugin can initialize even when
        // WordPress functions are not available (like in unit test environment)
        
        $this->assertInstanceOf( Plugin::class, $this->plugin );
        $this->assertInstanceOf( Container::class, $this->plugin->get_container() );
        
        // Services should still be available
        $this->assertNotNull( $this->plugin->get_logger() );
        $this->assertNotNull( $this->plugin->get_cache_manager() );
    }

    /**
     * Test full workflow integration.
     *
     * @return void
     */
    public function test_full_workflow_integration(): void {
        // Create a temporary directory with test files
        $test_dir = sys_get_temp_dir() . '/kapl_integration_test_' . uniqid();
        mkdir( $test_dir, 0755, true );
        
        // Create test PDF files
        file_put_contents( $test_dir . '/document1.pdf', 'Test content 1' );
        file_put_contents( $test_dir . '/document2.pdf', 'Test content 2' );
        
        try {
            // Test full workflow: scan -> build index -> search
            $index_builder = $this->plugin->get_index_builder();
            
            // Build index
            $build_result = $index_builder->build_index( [ $test_dir ] );
            $this->assertTrue( $build_result );
            
            // Get current index
            $current_index = $index_builder->get_current_index();
            $this->assertNotNull( $current_index );
            $this->assertCount( 2, $current_index );
            
            // Test statistics
            $stats = $index_builder->get_index_statistics();
            $this->assertEquals( 2, $stats['total_files'] );
            $this->assertGreaterThan( 0, $stats['total_size'] );
            
            // Clean up index
            $clear_result = $index_builder->clear_index();
            $this->assertTrue( $clear_result );
            
        } finally {
            // Clean up test directory
            unlink( $test_dir . '/document1.pdf' );
            unlink( $test_dir . '/document2.pdf' );
            rmdir( $test_dir );
        }
    }
}
