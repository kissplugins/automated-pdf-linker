<?php
/**
 * Test Runner Script
 *
 * Simple test runner for environments without Composer/PHPUnit
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

// Include bootstrap
require_once __DIR__ . '/tests/bootstrap.php';

echo "=== KISS PDF Linker - Test Runner ===\n\n";

/**
 * Simple test runner class.
 */
class SimpleTestRunner {
    
    private $tests_passed = 0;
    private $tests_failed = 0;
    private $test_files = [];
    
    /**
     * Run all tests.
     */
    public function run_all_tests() {
        $this->discover_tests();
        
        foreach ( $this->test_files as $test_file ) {
            $this->run_test_file( $test_file );
        }
        
        $this->print_summary();
    }
    
    /**
     * Discover test files.
     */
    private function discover_tests() {
        $test_dirs = [
            __DIR__ . '/tests/Unit',
            __DIR__ . '/tests/Integration'
        ];
        
        foreach ( $test_dirs as $dir ) {
            if ( is_dir( $dir ) ) {
                $this->scan_directory( $dir );
            }
        }
    }
    
    /**
     * Scan directory for test files.
     */
    private function scan_directory( $dir ) {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator( $dir )
        );
        
        foreach ( $iterator as $file ) {
            if ( $file->isFile() && 
                 $file->getExtension() === 'php' && 
                 strpos( $file->getFilename(), 'Test.php' ) !== false ) {
                $this->test_files[] = $file->getPathname();
            }
        }
    }
    
    /**
     * Run a single test file.
     */
    private function run_test_file( $test_file ) {
        echo "Running: " . basename( $test_file ) . "\n";
        
        try {
            require_once $test_file;
            
            // Get class name from file
            $class_name = $this->get_class_name_from_file( $test_file );
            
            if ( $class_name && class_exists( $class_name ) ) {
                $this->run_test_class( $class_name );
            } else {
                echo "  ❌ Could not find test class in file\n";
                $this->tests_failed++;
            }
            
        } catch ( Exception $e ) {
            echo "  ❌ Error loading test file: " . $e->getMessage() . "\n";
            $this->tests_failed++;
        }
        
        echo "\n";
    }
    
    /**
     * Get class name from file.
     */
    private function get_class_name_from_file( $file ) {
        $content = file_get_contents( $file );
        
        // Extract namespace
        preg_match( '/namespace\s+([^;]+);/', $content, $namespace_matches );
        $namespace = isset( $namespace_matches[1] ) ? $namespace_matches[1] : '';
        
        // Extract class name
        preg_match( '/class\s+(\w+)/', $content, $class_matches );
        $class = isset( $class_matches[1] ) ? $class_matches[1] : '';
        
        if ( $namespace && $class ) {
            return $namespace . '\\' . $class;
        }
        
        return $class;
    }
    
    /**
     * Run test class.
     */
    private function run_test_class( $class_name ) {
        try {
            $reflection = new ReflectionClass( $class_name );
            $instance = $reflection->newInstance();
            
            // Run setUp if it exists
            if ( method_exists( $instance, 'setUp' ) ) {
                $instance->setUp();
            }
            
            // Get all test methods
            $methods = $reflection->getMethods( ReflectionMethod::IS_PUBLIC );
            $test_methods = array_filter( $methods, function( $method ) {
                return strpos( $method->getName(), 'test_' ) === 0;
            } );
            
            foreach ( $test_methods as $method ) {
                $this->run_test_method( $instance, $method );
            }
            
            // Run tearDown if it exists
            if ( method_exists( $instance, 'tearDown' ) ) {
                $instance->tearDown();
            }
            
        } catch ( Exception $e ) {
            echo "  ❌ Error running test class: " . $e->getMessage() . "\n";
            $this->tests_failed++;
        }
    }
    
    /**
     * Run test method.
     */
    private function run_test_method( $instance, $method ) {
        $method_name = $method->getName();
        
        try {
            $method->invoke( $instance );
            echo "  ✅ {$method_name}\n";
            $this->tests_passed++;
            
        } catch ( Exception $e ) {
            echo "  ❌ {$method_name}: " . $e->getMessage() . "\n";
            $this->tests_failed++;
        }
    }
    
    /**
     * Print test summary.
     */
    private function print_summary() {
        $total = $this->tests_passed + $this->tests_failed;
        $success_rate = $total > 0 ? round( ( $this->tests_passed / $total ) * 100, 1 ) : 0;
        
        echo "=== Test Summary ===\n";
        echo "Total Tests: {$total}\n";
        echo "Passed: {$this->tests_passed}\n";
        echo "Failed: {$this->tests_failed}\n";
        echo "Success Rate: {$success_rate}%\n";
        
        if ( $this->tests_failed === 0 ) {
            echo "\n🎉 All tests passed!\n";
        } else {
            echo "\n❌ Some tests failed. Please review the output above.\n";
        }
    }
}

// Run the tests
$runner = new SimpleTestRunner();
$runner->run_all_tests();
