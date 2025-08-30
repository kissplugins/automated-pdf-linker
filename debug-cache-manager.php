<?php
/**
 * Debug Cache Manager Script
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

// Include the autoloader
require_once __DIR__ . '/src/autoloader.php';

use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

echo "=== KISS PDF Linker - Cache Manager Debug ===\n\n";

try {
    // Create logger and cache manager
    $logger = new Logger();
    $cache_manager = new CacheManager($logger);
    echo "✅ CacheManager created successfully\n";

    // Check current index
    $current_index = $cache_manager->get_index();
    if ($current_index === null) {
        echo "📋 Current index: NULL (empty)\n";
    } else {
        echo "📋 Current index: " . count($current_index) . " items\n";
        if (count($current_index) > 0) {
            echo "   First few items:\n";
            $count = 0;
            foreach ($current_index as $key => $item) {
                echo "   - {$key}\n";
                $count++;
                if ($count >= 3) break;
            }
            if (count($current_index) > 3) {
                echo "   ... and " . (count($current_index) - 3) . " more\n";
            }
        }
    }

    echo "\n--- Testing Cache Manager Operations ---\n";

    // Test data
    $test_data = [
        'debug-test.pdf' => [
            'path' => '/test/debug-test.pdf',
            'filename' => 'debug-test.pdf',
            'normalized_name' => 'debug-test',
            'size' => 2048
        ]
    ];

    echo "🔄 Storing test data...\n";
    $store_result = $cache_manager->update_index($test_data);
    echo "   Store result: " . ($store_result ? "SUCCESS" : "FAILED") . "\n";

    echo "🔍 Retrieving data...\n";
    $retrieved_data = $cache_manager->get_index();
    if ($retrieved_data === null) {
        echo "   Retrieved: NULL\n";
    } else {
        echo "   Retrieved: " . count($retrieved_data) . " items\n";
        if (isset($retrieved_data['debug-test.pdf'])) {
            echo "   ✅ Test data found!\n";
            echo "   Test item details:\n";
            foreach ($retrieved_data['debug-test.pdf'] as $key => $value) {
                echo "     {$key}: {$value}\n";
            }
        } else {
            echo "   ❌ Test data NOT found\n";
            echo "   Available keys:\n";
            foreach (array_keys($retrieved_data) as $key) {
                echo "     - {$key}\n";
            }
        }
    }

    echo "🗑️ Clearing index...\n";
    $clear_result = $cache_manager->clear_index();
    echo "   Clear result: " . ($clear_result ? "SUCCESS" : "FAILED") . "\n";

    echo "🔍 Verifying clear...\n";
    $cleared_data = $cache_manager->get_index();
    if ($cleared_data === null || empty($cleared_data)) {
        echo "   ✅ Index properly cleared\n";
    } else {
        echo "   ❌ Index not cleared, still has " . count($cleared_data) . " items\n";
    }

    echo "\n=== Debug Complete ===\n";

} catch (Exception $e) {
    echo "❌ Debug failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
