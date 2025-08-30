<?php
/**
 * Self Test Page
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

use KissPlugins\AutomatedPdfLinker\Core\Plugin;

/**
 * Self Test Page Class
 *
 * Dedicated page for running self-tests with comprehensive debugging.
 *
 * @since 3.0.0
 */
class SelfTestPage {

    /**
     * Page slug.
     */
    const PAGE_SLUG = 'kapl-self-test';

    /**
     * Plugin instance.
     *
     * @var Plugin
     */
    private $plugin;

    /**
     * Constructor.
     *
     * @param Plugin $plugin Plugin instance.
     */
    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Add admin menu item.
     *
     * @return void
     */
    public function add_admin_menu(): void {
        \add_management_page(
            \__( 'KISS PDF Linker Self-Tests', 'kiss-automated-pdf-linker' ),
            \__( 'KISS PDF Self-Tests', 'kiss-automated-pdf-linker' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render the self-test page.
     *
     * @return void
     */
    public function render_page(): void {
        // Check user capabilities
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'kiss-automated-pdf-linker' ) );
            return;
        }

        ?>
        <div class="wrap">
            <h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>
            
            <div style="background: #f0f8ff; border: 1px solid #0073aa; padding: 15px; margin: 20px 0; border-radius: 5px;">
                <h2>🔧 Debug Information</h2>
                <?php $this->render_debug_info(); ?>
            </div>

            <?php
            // Handle test execution
            $run_tests = isset( $_POST['kapl_run_selftest'] ) && 
                        \wp_verify_nonce( \sanitize_key( $_POST['kapl_selftest_nonce'] ?? '' ), 'kapl_selftest_action' );
            
            if ( $run_tests ) {
                $this->render_test_execution();
            } else {
                $this->render_test_form();
            }
            ?>
        </div>

        <style>
            .kapl-debug-section { margin: 15px 0; padding: 10px; border-left: 4px solid #0073aa; background: #f9f9f9; }
            .kapl-success { color: #006600; }
            .kapl-error { color: #cc0000; }
            .kapl-warning { color: #ff6600; }
            .kapl-test-result { margin: 10px 0; padding: 10px; border-left: 3px solid #46b450; background: #f0f8f0; }
            .kapl-test-result.failed { border-left-color: #dc3232; background: #fdf0f0; }
            .kapl-test-summary { padding: 15px; margin: 20px 0; border-left: 4px solid #46b450; background: #f9f9f9; }
            .kapl-test-summary.has-failures { border-left-color: #dc3232; }
        </style>
        <?php
    }

    /**
     * Render comprehensive debug information.
     *
     * @return void
     */
    private function render_debug_info(): void {
        echo '<div class="kapl-debug-section">';
        echo '<h3>📁 File System Status</h3>';
        
        $plugin_dir = \plugin_dir_path( dirname( dirname( __DIR__ ) ) . '/kiss-automated-pdf-linker-v3.php' );
        echo '<p><strong>Plugin Directory:</strong> ' . \esc_html( $plugin_dir ) . '</p>';
        
        $files_to_check = [
            'kiss-automated-pdf-linker-v3.php' => 'Main Plugin File',
            'vendor/autoload.php' => 'Autoloader',
            'src/Core/Plugin.php' => 'Core Plugin Class',
            'src/Admin/SelfTest.php' => 'SelfTest Class',
            'src/Admin/SelfTestPage.php' => 'SelfTest Page Class',
        ];
        
        echo '<ul>';
        foreach ( $files_to_check as $file => $description ) {
            $path = $plugin_dir . $file;
            $exists = \file_exists( $path );
            $readable = $exists && \is_readable( $path );
            
            if ( $exists && $readable ) {
                echo '<li class="kapl-success">✅ <strong>' . \esc_html( $description ) . ':</strong> OK</li>';
            } elseif ( $exists ) {
                echo '<li class="kapl-warning">⚠️ <strong>' . \esc_html( $description ) . ':</strong> Exists but not readable</li>';
            } else {
                echo '<li class="kapl-error">❌ <strong>' . \esc_html( $description ) . ':</strong> Missing</li>';
            }
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="kapl-debug-section">';
        echo '<h3>🔧 PHP Environment</h3>';
        echo '<ul>';
        echo '<li><strong>PHP Version:</strong> ' . \PHP_VERSION . '</li>';
        echo '<li><strong>WordPress Version:</strong> ' . \get_bloginfo( 'version' ) . '</li>';
        echo '<li><strong>Memory Limit:</strong> ' . \ini_get( 'memory_limit' ) . '</li>';
        echo '<li><strong>Max Execution Time:</strong> ' . \ini_get( 'max_execution_time' ) . 's</li>';
        echo '<li><strong>Error Reporting:</strong> ' . \error_reporting() . '</li>';
        echo '</ul>';
        echo '</div>';

        echo '<div class="kapl-debug-section">';
        echo '<h3>📦 Class Loading Status</h3>';
        
        $test_classes = [
            'KissPlugins\\AutomatedPdfLinker\\Core\\Plugin' => 'Core Plugin',
            'KissPlugins\\AutomatedPdfLinker\\Admin\\SelfTest' => 'SelfTest System',
            'KissPlugins\\AutomatedPdfLinker\\Utils\\FileNormalizer' => 'File Normalizer',
            'KissPlugins\\AutomatedPdfLinker\\Services\\CacheManager' => 'Cache Manager',
        ];
        
        echo '<ul>';
        foreach ( $test_classes as $class => $description ) {
            if ( \class_exists( $class ) ) {
                echo '<li class="kapl-success">✅ <strong>' . \esc_html( $description ) . ':</strong> Loaded</li>';
            } else {
                echo '<li class="kapl-error">❌ <strong>' . \esc_html( $description ) . ':</strong> Not found</li>';
            }
        }
        echo '</ul>';
        echo '</div>';

        echo '<div class="kapl-debug-section">';
        echo '<h3>🔌 Plugin Status</h3>';
        
        try {
            $plugin_version = $this->plugin->get_version();
            echo '<p class="kapl-success">✅ <strong>Plugin Instance:</strong> Active (v' . \esc_html( $plugin_version ) . ')</p>';
            
            $settings = $this->plugin->get_settings()->get_settings();
            echo '<p class="kapl-success">✅ <strong>Settings:</strong> Accessible (' . \count( $settings ) . ' keys)</p>';
            
        } catch ( \Exception $e ) {
            echo '<p class="kapl-error">❌ <strong>Plugin Instance Error:</strong> ' . \esc_html( $e->getMessage() ) . '</p>';
        }
        echo '</div>';
    }

    /**
     * Render test execution form.
     *
     * @return void
     */
    private function render_test_form(): void {
        ?>
        <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 5px;">
            <h2>🧪 Run Self-Tests</h2>
            <p>Click the button below to run comprehensive diagnostic tests on the KISS PDF Linker plugin.</p>
            
            <form method="post" action="">
                <?php \wp_nonce_field( 'kapl_selftest_action', 'kapl_selftest_nonce' ); ?>
                <p>
                    <button type="submit" name="kapl_run_selftest" class="button button-primary button-large">
                        🚀 Run All Self-Tests
                    </button>
                </p>
            </form>
            
            <h3>📋 Tests Included:</h3>
            <ul>
                <li><strong>Core Functionality:</strong> Plugin initialization, autoloader, settings</li>
                <li><strong>Service Layer:</strong> Cache manager, file normalizer, fuzzy matcher, logger</li>
                <li><strong>WordPress Integration:</strong> Shortcode registration, admin menu, uploads directory</li>
                <li><strong>Index System:</strong> Index functionality and validation</li>
            </ul>
        </div>
        <?php
    }

    /**
     * Render test execution and results.
     *
     * @return void
     */
    private function render_test_execution(): void {
        ?>
        <div style="background: white; padding: 20px; border: 1px solid #ccc; border-radius: 5px;">
            <h2>🧪 Self-Test Execution</h2>
            
            <?php
            echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 3px;">';
            echo '<strong>Execution Started:</strong> ' . \current_time( 'Y-m-d H:i:s' ) . '<br>';
            echo '<strong>Memory Usage:</strong> ' . \size_format( \memory_get_usage() ) . '<br>';
            echo '</div>';
            
            try {
                // Check if SelfTest class exists
                if ( ! \class_exists( 'KissPlugins\\AutomatedPdfLinker\\Admin\\SelfTest' ) ) {
                    throw new \Exception( 'SelfTest class not found. Autoloader may not be working.' );
                }
                
                echo '<p class="kapl-success">✅ SelfTest class loaded successfully</p>';
                
                // Create SelfTest instance
                $self_test = new SelfTest( $this->plugin );
                echo '<p class="kapl-success">✅ SelfTest instance created</p>';
                
                // Run tests
                echo '<p>🔄 Running tests...</p>';
                $results = $self_test->run_all_tests();
                $summary = $self_test->get_test_summary();
                
                echo '<p class="kapl-success">✅ Tests completed successfully</p>';
                
                // Display results
                $this->render_test_results( $results, $summary );
                
            } catch ( \Exception $e ) {
                echo '<div style="color: red; padding: 15px; border: 2px solid red; background: #ffe6e6; border-radius: 5px; margin: 15px 0;">';
                echo '<h3>❌ Test Execution Failed</h3>';
                echo '<p><strong>Error:</strong> ' . \esc_html( $e->getMessage() ) . '</p>';
                echo '<p><strong>File:</strong> ' . \esc_html( $e->getFile() ) . '</p>';
                echo '<p><strong>Line:</strong> ' . \esc_html( $e->getLine() ) . '</p>';
                echo '<details><summary>Stack Trace</summary><pre>' . \esc_html( $e->getTraceAsString() ) . '</pre></details>';
                echo '</div>';
            }
            
            echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border-radius: 3px;">';
            echo '<strong>Execution Completed:</strong> ' . \current_time( 'Y-m-d H:i:s' ) . '<br>';
            echo '<strong>Peak Memory:</strong> ' . \size_format( \memory_get_peak_usage() ) . '<br>';
            echo '</div>';
            ?>
            
            <form method="post" action="">
                <?php \wp_nonce_field( 'kapl_selftest_action', 'kapl_selftest_nonce' ); ?>
                <p>
                    <button type="submit" name="kapl_run_selftest" class="button button-secondary">
                        🔄 Run Tests Again
                    </button>
                    <a href="<?php echo \esc_url( \admin_url( 'tools.php?page=' . self::PAGE_SLUG ) ); ?>" class="button">
                        ← Back to Test Form
                    </a>
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Render test results.
     *
     * @param array $results Test results.
     * @param array $summary Test summary.
     * @return void
     */
    private function render_test_results( array $results, array $summary ): void {
        $has_failures = $summary['failed'] > 0;
        ?>
        <div class="kapl-test-summary <?php echo $has_failures ? 'has-failures' : ''; ?>">
            <h3>📊 Test Summary</h3>
            <p><strong>
                <?php
                printf(
                    /* translators: 1: passed tests, 2: total tests, 3: success rate */
                    \esc_html__( 'Results: %1$d/%2$d tests passed (%3$s%% success rate)', 'kiss-automated-pdf-linker' ),
                    $summary['passed'],
                    $summary['total'],
                    $summary['success_rate']
                );
                ?>
            </strong></p>
            
            <?php if ( $has_failures ): ?>
                <p style="color: #cc0000;">⚠️ Some tests failed. Review the details below for troubleshooting information.</p>
            <?php else: ?>
                <p style="color: #006600;">🎉 All tests passed! The plugin is functioning correctly.</p>
            <?php endif; ?>
        </div>

        <h3>📋 Detailed Results</h3>
        <div class="kapl-test-details">
            <?php foreach ( $results as $result ): ?>
                <div class="kapl-test-result <?php echo $result['passed'] ? '' : 'failed'; ?>">
                    <strong style="color: <?php echo $result['passed'] ? '#006600' : '#cc0000'; ?>;">
                        <?php echo $result['passed'] ? '✅' : '❌'; ?> <?php echo \esc_html( $result['name'] ); ?>
                    </strong>
                    <br>
                    <span style="font-size: 0.9em; color: #666; line-height: 1.4;">
                        <?php echo \esc_html( $result['message'] ); ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
    }
}
