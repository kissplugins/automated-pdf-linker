<?php
/**
 * Self Test System
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

use KissPlugins\AutomatedPdfLinker\Core\Plugin;
use KissPlugins\AutomatedPdfLinker\Utils\FileNormalizer;
use KissPlugins\AutomatedPdfLinker\Services\FuzzyMatcher;

/**
 * Self Test Class
 *
 * Provides on-screen diagnostic tests for regression prevention.
 *
 * @since 3.0.0
 */
class SelfTest {

    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Test results.
     *
     * @var array
     */
    private $test_results = [];

    /**
     * Constructor.
     *
     * @param Plugin $plugin Plugin instance.
     */
    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Run all self-tests.
     *
     * @return array Test results.
     */
    public function run_all_tests(): array {
        $this->test_results = [];

        // Core functionality tests
        $this->test_plugin_initialization();
        $this->test_autoloader();
        $this->test_dependency_injection();
        $this->test_settings_access();
        
        // Service tests
        $this->test_cache_manager();
        $this->test_file_normalizer();
        $this->test_fuzzy_matcher();
        $this->test_logger();
        
        // WordPress integration tests
        $this->test_shortcode_registration();
        $this->test_admin_menu();
        $this->test_uploads_directory();
        
        // Index tests
        $this->test_index_functionality();
        
        return $this->test_results;
    }

    /**
     * Test plugin initialization.
     *
     * @return void
     */
    private function test_plugin_initialization(): void {
        $test_name = 'Plugin Initialization';
        
        try {
            $plugin = Plugin::get_instance();
            $version = $plugin->get_version();
            
            if ( $version === '3.0.0' ) {
                $this->add_test_result( $test_name, true, 'Plugin initialized successfully with correct version.' );
            } else {
                $this->add_test_result( $test_name, false, "Version mismatch. Expected '3.0.0', got '{$version}'." );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Plugin initialization failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test autoloader functionality.
     *
     * @return void
     */
    private function test_autoloader(): void {
        $test_name = 'PSR-4 Autoloader';
        
        try {
            // Test if classes can be loaded
            $test_classes = [
                'KissPlugins\\AutomatedPdfLinker\\Utils\\FileNormalizer',
                'KissPlugins\\AutomatedPdfLinker\\Services\\FuzzyMatcher',
                'KissPlugins\\AutomatedPdfLinker\\Services\\CacheManager',
            ];
            
            $loaded_classes = [];
            foreach ( $test_classes as $class ) {
                if ( class_exists( $class ) ) {
                    $loaded_classes[] = $class;
                }
            }
            
            if ( count( $loaded_classes ) === count( $test_classes ) ) {
                $this->add_test_result( $test_name, true, 'All test classes loaded successfully via autoloader.' );
            } else {
                $missing = array_diff( $test_classes, $loaded_classes );
                $this->add_test_result( $test_name, false, 'Failed to load classes: ' . implode( ', ', $missing ) );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Autoloader test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test dependency injection container.
     *
     * @return void
     */
    private function test_dependency_injection(): void {
        $test_name = 'Dependency Injection Container';

        try {
            $container = $this->plugin->get_container();

            if ( null === $container ) {
                $this->add_test_result( $test_name, false, 'Container not initialized.' );
                return;
            }

            // Test basic container functionality
            $test_services = [
                'logger' => 'Logger service',
                'cache_manager' => 'Cache Manager service',
                'index_builder' => 'Index Builder service',
                'settings' => 'Settings service',
            ];

            $available_services = [];
            $missing_services = [];

            foreach ( $test_services as $service_id => $description ) {
                if ( $container->has( $service_id ) ) {
                    $available_services[] = $description;
                } else {
                    $missing_services[] = $description;
                }
            }

            if ( empty( $missing_services ) ) {
                $this->add_test_result(
                    $test_name,
                    true,
                    'Container initialized with ' . count( $available_services ) . ' core services.'
                );
            } else {
                $this->add_test_result(
                    $test_name,
                    false,
                    'Missing services: ' . implode( ', ', $missing_services )
                );
            }

        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Container test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test settings access.
     *
     * @return void
     */
    private function test_settings_access(): void {
        $test_name = 'Settings Access';
        
        try {
            $settings = $this->plugin->get_settings()->get_settings();
            
            $required_keys = [ 'selected_directories', 'link_color', 'use_product_title_match', 'debug_logging' ];
            $missing_keys = array_diff( $required_keys, array_keys( $settings ) );
            
            if ( empty( $missing_keys ) ) {
                $this->add_test_result( $test_name, true, 'All required settings keys present.' );
            } else {
                $this->add_test_result( $test_name, false, 'Missing settings keys: ' . implode( ', ', $missing_keys ) );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Settings access failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test cache manager.
     *
     * @return void
     */
    private function test_cache_manager(): void {
        $test_name = 'Cache Manager';

        try {
            $cache_manager = $this->plugin->get_cache_manager();

            if ( null === $cache_manager ) {
                $this->add_test_result( $test_name, false, 'Cache manager not initialized.' );
                return;
            }

            // Store the original index to restore later
            $original_index = $cache_manager->get_index();
            $original_count = $original_index ? count( $original_index ) : 0;

            // Test with sample data (using associative array format)
            $test_data = [
                'selftest-sample.pdf' => [
                    'path' => 'test/selftest-sample.pdf',
                    'filename' => 'selftest-sample.pdf',
                    'normalized_name' => 'selftest-sample',
                    'size' => 1024
                ]
            ];

            // Test store operation (this replaces the entire index)
            $update_result = $cache_manager->update_index( $test_data );
            if ( ! $update_result ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result( $test_name, false, 'Failed to store test data. Check file permissions and WordPress database access.' );
                return;
            }

            // Test retrieve operation
            $retrieved_data = $cache_manager->get_index();
            if ( null === $retrieved_data ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result( $test_name, false, 'Failed to retrieve stored data. Storage mechanism may not be working.' );
                return;
            }

            // Verify we got exactly our test data (should be 1 item)
            if ( count( $retrieved_data ) !== 1 ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result(
                    $test_name,
                    false,
                    'Retrieved data count mismatch. Expected 1 test item, got ' . count( $retrieved_data ) . ' items.'
                );
                return;
            }

            // Check if our test data exists and is correct
            if ( ! isset( $retrieved_data['selftest-sample.pdf'] ) ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result( $test_name, false, 'Test data not found in retrieved index.' );
                return;
            }

            // Verify specific test data integrity
            $test_item = $retrieved_data['selftest-sample.pdf'];
            if ( $test_item['normalized_name'] !== 'selftest-sample' ||
                 $test_item['filename'] !== 'selftest-sample.pdf' ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result( $test_name, false, 'Test data integrity check failed.' );
                return;
            }

            // Test clear operation
            $clear_result = $cache_manager->clear_index();
            if ( ! $clear_result ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result( $test_name, false, 'Failed to clear index.' );
                return;
            }

            // Verify index is cleared
            $cleared_data = $cache_manager->get_index();
            if ( $cleared_data !== null && ! empty( $cleared_data ) ) {
                // Restore original index before failing
                if ( $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
                $this->add_test_result( $test_name, false, 'Index not properly cleared.' );
                return;
            }

            // Restore original index
            if ( $original_index !== null && ! empty( $original_index ) ) {
                $restore_result = $cache_manager->update_index( $original_index );
                if ( ! $restore_result ) {
                    $this->add_test_result( $test_name, false, 'Test passed but failed to restore original index.' );
                    return;
                }
            }

            // Determine storage method for informative message
            $storage_method = 'unknown';
            if ( function_exists( 'get_option' ) ) {
                $is_file_storage = \get_option( 'kapl_pdf_index_file_storage', false );
                $storage_method = $is_file_storage ? 'file-based' : 'database';
            } else {
                $storage_method = 'file-based (WordPress functions unavailable)';
            }

            $this->add_test_result(
                $test_name,
                true,
                "Cache manager store/retrieve/clear working correctly using {$storage_method} storage. Original index ({$original_count} items) restored."
            );

        } catch ( \Exception $e ) {
            // Try to restore original index on exception
            try {
                if ( isset( $original_index ) && $original_index !== null ) {
                    $cache_manager->update_index( $original_index );
                }
            } catch ( \Exception $restore_e ) {
                // Ignore restore errors in exception handler
            }

            $this->add_test_result( $test_name, false, 'Cache manager test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test file normalizer.
     *
     * @return void
     */
    private function test_file_normalizer(): void {
        $test_name = 'File Normalizer';
        
        try {
            $test_cases = [
                [ 'input' => 'Test Document.pdf', 'expected' => 'test-document' ],
                [ 'input' => 'BlueBerryStrain.pdf', 'expected' => 'blue-berry-strain' ],
                [ 'input' => '3.5 Gram THCA (Limited) – Pressure.pdf', 'expected' => '3-5-gram-thca-limited-pressure' ],
                [ 'input' => '', 'expected' => '' ],
            ];
            
            $failed_cases = [];
            foreach ( $test_cases as $case ) {
                $result = FileNormalizer::normalize( $case['input'] );
                if ( $result !== $case['expected'] ) {
                    $failed_cases[] = "'{$case['input']}' → '{$result}' (expected '{$case['expected']}')";
                }
            }
            
            if ( empty( $failed_cases ) ) {
                $this->add_test_result( $test_name, true, 'All normalization test cases passed.' );
            } else {
                $this->add_test_result( $test_name, false, 'Failed cases: ' . implode( '; ', $failed_cases ) );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'File normalizer test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test fuzzy matcher.
     *
     * @return void
     */
    private function test_fuzzy_matcher(): void {
        $test_name = 'Fuzzy Matcher';
        
        try {
            $logger = $this->plugin->get_logger();
            $matcher = new FuzzyMatcher( $logger );
            
            $test_index = [
                [ 'path' => 'test1.pdf', 'filename' => 'test1.pdf', 'normalized_name' => 'blue-berry-strain' ],
                [ 'path' => 'test2.pdf', 'filename' => 'test2.pdf', 'normalized_name' => 'strawberry-kush' ],
            ];
            
            // Test exact match
            $result = $matcher->find_best_match( 'blue berry strain', $test_index );
            if ( $result && $result['normalized_name'] === 'blue-berry-strain' ) {
                $this->add_test_result( $test_name, true, 'Fuzzy matching working correctly.' );
            } else {
                $this->add_test_result( $test_name, false, 'Fuzzy matching failed to find expected match.' );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Fuzzy matcher test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test logger functionality.
     *
     * @return void
     */
    private function test_logger(): void {
        $test_name = 'Logger';
        
        try {
            $logger = $this->plugin->get_logger();
            
            // Test that logger methods exist and are callable
            $methods = [ 'debug', 'info', 'warn', 'error', 'log' ];
            $missing_methods = [];
            
            foreach ( $methods as $method ) {
                if ( ! method_exists( $logger, $method ) ) {
                    $missing_methods[] = $method;
                }
            }
            
            if ( empty( $missing_methods ) ) {
                $this->add_test_result( $test_name, true, 'All logger methods available.' );
            } else {
                $this->add_test_result( $test_name, false, 'Missing logger methods: ' . implode( ', ', $missing_methods ) );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Logger test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test shortcode registration.
     *
     * @return void
     */
    private function test_shortcode_registration(): void {
        $test_name = 'Shortcode Registration';
        
        try {
            global $shortcode_tags;
            
            if ( isset( $shortcode_tags['kiss_pdf'] ) ) {
                $this->add_test_result( $test_name, true, 'Shortcode [kiss_pdf] is registered.' );
            } else {
                $this->add_test_result( $test_name, false, 'Shortcode [kiss_pdf] is not registered.' );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Shortcode registration test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test admin menu registration.
     *
     * @return void
     */
    private function test_admin_menu(): void {
        $test_name = 'Admin Menu';
        
        try {
            // Check if the admin menu hook exists
            if ( \has_action( 'admin_menu' ) ) {
                $this->add_test_result( $test_name, true, 'Admin menu hooks are registered.' );
            } else {
                $this->add_test_result( $test_name, false, 'Admin menu hooks are missing.' );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Admin menu test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test uploads directory access.
     *
     * @return void
     */
    private function test_uploads_directory(): void {
        $test_name = 'Uploads Directory Access';
        
        try {
            $upload_dir = \wp_upload_dir();
            
            if ( $upload_dir['error'] ) {
                $this->add_test_result( $test_name, false, 'WordPress uploads directory error: ' . $upload_dir['error'] );
                return;
            }
            
            $uploads_path = $upload_dir['basedir'];
            
            if ( ! is_dir( $uploads_path ) ) {
                $this->add_test_result( $test_name, false, 'Uploads directory does not exist: ' . $uploads_path );
                return;
            }
            
            if ( ! is_readable( $uploads_path ) ) {
                $this->add_test_result( $test_name, false, 'Uploads directory is not readable: ' . $uploads_path );
                return;
            }
            
            $this->add_test_result( $test_name, true, 'Uploads directory is accessible: ' . $uploads_path );
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Uploads directory test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Test index functionality.
     *
     * @return void
     */
    private function test_index_functionality(): void {
        $test_name = 'Index Functionality';
        
        try {
            $index_builder = $this->plugin->get_index_builder();
            $stats = $index_builder->get_index_stats();
            $validation = $index_builder->validate_index();
            
            if ( in_array( $stats['status'], [ 'ready', 'empty', 'not_found' ], true ) ) {
                if ( $stats['status'] === 'ready' && $validation['is_valid'] ) {
                    $this->add_test_result( $test_name, true, "Index is ready with {$stats['total_files']} files and passes validation." );
                } elseif ( $stats['status'] === 'empty' ) {
                    $this->add_test_result( $test_name, true, 'Index is empty but functional. Build index to populate.' );
                } elseif ( $stats['status'] === 'not_found' ) {
                    $this->add_test_result( $test_name, true, 'No index found but system is functional. Build index to create.' );
                } else {
                    $this->add_test_result( $test_name, false, 'Index validation failed: ' . implode( '; ', $validation['errors'] ) );
                }
            } else {
                $this->add_test_result( $test_name, false, 'Unknown index status: ' . $stats['status'] );
            }
        } catch ( \Exception $e ) {
            $this->add_test_result( $test_name, false, 'Index functionality test failed: ' . $e->getMessage() );
        }
    }

    /**
     * Add test result.
     *
     * @param string $test_name Test name.
     * @param bool   $passed    Whether test passed.
     * @param string $message   Test message.
     * @return void
     */
    private function add_test_result( string $test_name, bool $passed, string $message ): void {
        $this->test_results[] = [
            'name'    => $test_name,
            'passed'  => $passed,
            'message' => $message,
        ];
    }

    /**
     * Get test summary.
     *
     * @return array Test summary.
     */
    public function get_test_summary(): array {
        $total = count( $this->test_results );
        $passed = count( array_filter( $this->test_results, function( $result ) {
            return $result['passed'];
        } ) );
        $failed = $total - $passed;
        
        return [
            'total'  => $total,
            'passed' => $passed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round( ( $passed / $total ) * 100, 1 ) : 0,
        ];
    }
}
