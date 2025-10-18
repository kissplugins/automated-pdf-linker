<?php
/**
 * KISS Automated PDF Linker Debug Page
 * 
 * Temporary debug page to help troubleshoot plugin loading issues.
 * Place this file in the plugin directory and access via browser.
 */

// Basic WordPress check
if ( ! defined( 'ABSPATH' ) ) {
    // If not in WordPress, try to load WordPress
    $wp_load_paths = [
        '../../../wp-load.php',
        '../../../../wp-load.php',
        '../../../../../wp-load.php',
    ];
    
    $wp_loaded = false;
    foreach ( $wp_load_paths as $path ) {
        if ( file_exists( __DIR__ . '/' . $path ) ) {
            require_once __DIR__ . '/' . $path;
            $wp_loaded = true;
            break;
        }
    }
    
    if ( ! $wp_loaded ) {
        die( 'WordPress not found. Please access this file through WordPress admin.' );
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>KISS Automated PDF Linker - Debug</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .debug-section { margin: 20px 0; padding: 15px; border: 1px solid #ccc; }
        .success { color: green; }
        .error { color: red; }
        .warning { color: orange; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>KISS Automated PDF Linker - Debug Information</h1>
    
    <div class="debug-section">
        <h2>1. File System Check</h2>
        <?php
        $plugin_dir = __DIR__;
        echo "<p><strong>Plugin Directory:</strong> {$plugin_dir}</p>";
        
        $files_to_check = [
            'kiss-automated-pdf-linker.php' => 'Old v2 Plugin File',
            'kiss-automated-pdf-linker-v3.php' => 'New v3 Plugin File',
            'vendor/autoload.php' => 'Autoloader',
            'src/Core/Plugin.php' => 'Main Plugin Class',
            'src/Admin/SelfTest.php' => 'SelfTest Class',
            'composer.json' => 'Composer Config',
        ];
        
        echo '<ul>';
        foreach ( $files_to_check as $file => $description ) {
            $path = $plugin_dir . '/' . $file;
            $exists = file_exists( $path );
            $class = $exists ? 'success' : 'error';
            $status = $exists ? '✅ Exists' : '❌ Missing';
            echo "<li class='{$class}'><strong>{$description}:</strong> {$status} ({$file})</li>";
        }
        echo '</ul>';
        ?>
    </div>
    
    <div class="debug-section">
        <h2>2. WordPress Plugin Status</h2>
        <?php
        if ( function_exists( 'get_plugins' ) ) {
            $all_plugins = get_plugins();
            $active_plugins = get_option( 'active_plugins', [] );
            
            echo '<h3>KAPL Related Plugins:</h3>';
            echo '<ul>';
            foreach ( $all_plugins as $plugin_file => $plugin_data ) {
                if ( strpos( $plugin_data['Name'], 'KISS' ) !== false || 
                     strpos( $plugin_data['Name'], 'PDF Linker' ) !== false ||
                     strpos( $plugin_file, 'kiss-automated-pdf-linker' ) !== false ) {
                    
                    $is_active = in_array( $plugin_file, $active_plugins );
                    $status = $is_active ? '🟢 Active' : '⚪ Inactive';
                    $version = $plugin_data['Version'] ?? 'Unknown';
                    
                    echo "<li><strong>{$plugin_data['Name']}</strong> (v{$version}) - {$status}<br>";
                    echo "<small>File: {$plugin_file}</small></li>";
                }
            }
            echo '</ul>';
        } else {
            echo '<p class="error">❌ get_plugins() function not available</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>3. Autoloader Test</h2>
        <?php
        $autoloader_path = $plugin_dir . '/vendor/autoload.php';
        
        if ( file_exists( $autoloader_path ) ) {
            echo '<p class="success">✅ Autoloader file exists</p>';
            
            try {
                require_once $autoloader_path;
                echo '<p class="success">✅ Autoloader loaded successfully</p>';
                
                // Test class loading
                $test_classes = [
                    'KissPlugins\\AutomatedPdfLinker\\Core\\Plugin',
                    'KissPlugins\\AutomatedPdfLinker\\Admin\\SelfTest',
                    'KissPlugins\\AutomatedPdfLinker\\Utils\\FileNormalizer',
                ];
                
                echo '<h3>Class Loading Test:</h3>';
                echo '<ul>';
                foreach ( $test_classes as $class ) {
                    $exists = class_exists( $class );
                    $status = $exists ? '✅ Loaded' : '❌ Failed';
                    $class_name = $exists ? 'success' : 'error';
                    echo "<li class='{$class_name}'><strong>{$class}:</strong> {$status}</li>";
                }
                echo '</ul>';
                
            } catch ( Exception $e ) {
                echo '<p class="error">❌ Autoloader error: ' . esc_html( $e->getMessage() ) . '</p>';
            }
        } else {
            echo '<p class="error">❌ Autoloader file missing</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>4. Plugin Instance Test</h2>
        <?php
        try {
            if ( class_exists( 'KissPlugins\\AutomatedPdfLinker\\Core\\Plugin' ) ) {
                echo '<p class="success">✅ Plugin class exists</p>';
                
                // Try to get instance (this might fail if not properly initialized)
                $plugin_file = $plugin_dir . '/kiss-automated-pdf-linker-v3.php';
                $plugin = \KissPlugins\AutomatedPdfLinker\Core\Plugin::get_instance( $plugin_file );
                echo '<p class="success">✅ Plugin instance created</p>';
                echo '<p>Plugin version: ' . $plugin->get_version() . '</p>';
                
            } else {
                echo '<p class="error">❌ Plugin class not found</p>';
            }
        } catch ( Exception $e ) {
            echo '<p class="error">❌ Plugin instance error: ' . esc_html( $e->getMessage() ) . '</p>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>5. Self-Test System Test</h2>
        <?php
        try {
            if ( class_exists( 'KissPlugins\\AutomatedPdfLinker\\Admin\\SelfTest' ) ) {
                echo '<p class="success">✅ SelfTest class exists</p>';
                
                if ( isset( $plugin ) ) {
                    $self_test = new \KissPlugins\AutomatedPdfLinker\Admin\SelfTest( $plugin );
                    echo '<p class="success">✅ SelfTest instance created</p>';
                    
                    $results = $self_test->run_all_tests();
                    $summary = $self_test->get_test_summary();
                    
                    echo '<p class="success">✅ Tests executed successfully</p>';
                    echo "<p><strong>Summary:</strong> {$summary['passed']}/{$summary['total']} tests passed ({$summary['success_rate']}% success rate)</p>";
                    
                    echo '<h3>Test Results:</h3>';
                    echo '<ul>';
                    foreach ( $results as $result ) {
                        $status = $result['passed'] ? '✅' : '❌';
                        $class = $result['passed'] ? 'success' : 'error';
                        echo "<li class='{$class}'>{$status} <strong>{$result['name']}:</strong> {$result['message']}</li>";
                    }
                    echo '</ul>';
                    
                } else {
                    echo '<p class="error">❌ Plugin instance not available</p>';
                }
            } else {
                echo '<p class="error">❌ SelfTest class not found</p>';
            }
        } catch ( Exception $e ) {
            echo '<p class="error">❌ Self-test error: ' . esc_html( $e->getMessage() ) . '</p>';
            echo '<pre>' . esc_html( $e->getTraceAsString() ) . '</pre>';
        }
        ?>
    </div>
    
    <div class="debug-section">
        <h2>6. WordPress Environment</h2>
        <?php
        echo '<ul>';
        echo '<li><strong>WordPress Version:</strong> ' . get_bloginfo( 'version' ) . '</li>';
        echo '<li><strong>PHP Version:</strong> ' . PHP_VERSION . '</li>';
        echo '<li><strong>Plugin URL:</strong> ' . plugins_url( '', __FILE__ ) . '</li>';
        echo '<li><strong>Plugin Path:</strong> ' . plugin_dir_path( __FILE__ ) . '</li>';
        echo '<li><strong>Current User Can Manage Options:</strong> ' . ( current_user_can( 'manage_options' ) ? 'Yes' : 'No' ) . '</li>';
        echo '</ul>';
        ?>
    </div>
    
    <div class="debug-section">
        <h2>7. Recommendations</h2>
        <h3>To Fix Plugin Conflicts:</h3>
        <ol>
            <li><strong>Deactivate the old version:</strong> Go to Plugins → Installed Plugins and deactivate "KISS Automated PDF Linker" (the one without v3.0.0)</li>
            <li><strong>Activate the new version:</strong> Activate "KISS Automated PDF Linker" v3.0.0</li>
            <li><strong>Optional:</strong> Delete the old <code>kiss-automated-pdf-linker.php</code> file</li>
        </ol>
        
        <h3>To Fix Self-Test Issues:</h3>
        <ol>
            <li>Ensure only v3.0.0 is active</li>
            <li>Check file permissions on the plugin directory</li>
            <li>Try deactivating and reactivating the plugin</li>
            <li>Check for PHP errors in your error log</li>
        </ol>
    </div>
    
</body>
</html>
