<?php
/**
 * Quick Self-Test Manual Execution
 * 
 * Add this to your WordPress admin or run via WP-CLI
 */

// Ensure WordPress is loaded
if ( ! defined( 'ABSPATH' ) ) {
    die( 'WordPress not loaded' );
}

// Load the autoloader
$autoloader_path = plugin_dir_path( __FILE__ ) . 'vendor/autoload.php';
if ( file_exists( $autoloader_path ) ) {
    require_once $autoloader_path;
    echo "✅ Autoloader loaded\n";
} else {
    die( "❌ Autoloader not found at: {$autoloader_path}\n" );
}

// Test class loading
if ( class_exists( 'KissPlugins\\AutomatedPdfLinker\\Core\\Plugin' ) ) {
    echo "✅ Plugin class exists\n";
} else {
    die( "❌ Plugin class not found\n" );
}

if ( class_exists( 'KissPlugins\\AutomatedPdfLinker\\Admin\\SelfTest' ) ) {
    echo "✅ SelfTest class exists\n";
} else {
    die( "❌ SelfTest class not found\n" );
}

// Try to run self-tests
try {
    $plugin_file = plugin_dir_path( __FILE__ ) . 'kiss-automated-pdf-linker-v3.php';
    $plugin = \KissPlugins\AutomatedPdfLinker\Core\Plugin::get_instance( $plugin_file );
    echo "✅ Plugin instance created\n";
    
    $self_test = new \KissPlugins\AutomatedPdfLinker\Admin\SelfTest( $plugin );
    echo "✅ SelfTest instance created\n";
    
    $results = $self_test->run_all_tests();
    $summary = $self_test->get_test_summary();
    
    echo "✅ Tests executed successfully\n";
    echo "Summary: {$summary['passed']}/{$summary['total']} tests passed ({$summary['success_rate']}% success rate)\n";
    
    foreach ( $results as $result ) {
        $status = $result['passed'] ? '✅' : '❌';
        echo "{$status} {$result['name']}: {$result['message']}\n";
    }
    
} catch ( Exception $e ) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . "\n";
    echo "Line: " . $e->getLine() . "\n";
}
?>
