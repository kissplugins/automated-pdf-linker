<?php
/**
 * Admin Menu
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

/**
 * Admin Menu Class
 *
 * Handles admin menu creation and settings page rendering.
 *
 * @since 3.0.0
 */
class AdminMenu {

    /**
     * Settings instance.
     *
     * @var Settings
     */
    private $settings;

    /**
     * Constructor.
     *
     * @param Settings $settings Settings instance.
     */
    public function __construct( Settings $settings ) {
        $this->settings = $settings;
    }

    /**
     * Add admin menu item under the 'Tools' menu.
     *
     * @return void
     */
    public function add_admin_menu(): void {
        \add_management_page(
            \__( 'KISS PDF Linker Settings', 'kiss-automated-pdf-linker' ),
            \__( 'KISS PDF Linker', 'kiss-automated-pdf-linker' ),
            'manage_options',
            Settings::SETTINGS_SLUG,
            [ $this, 'render_settings_page' ]
        );
    }

    /**
     * Render the settings page HTML.
     *
     * @return void
     */
    public function render_settings_page(): void {
        // Check user capabilities
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'kiss-automated-pdf-linker' ) );
            return;
        }

        // Handle index rebuilding action
        $this->settings->handle_index_rebuild();

        ?>
        <div class="wrap">
            <h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>

            <?php \settings_errors( 'kapl_rebuild_status' ); ?>

            <form method="post" action="options.php">
                <?php
                \settings_fields( 'kapl_settings_group' );
                \do_settings_sections( Settings::SETTINGS_SLUG );
                \submit_button( \__( 'Save Settings', 'kiss-automated-pdf-linker' ) );
                ?>
            </form>

            <hr>

            <h2><?php \esc_html_e( 'PDF Index Management', 'kiss-automated-pdf-linker' ); ?></h2>
            <p><?php \esc_html_e( 'After saving directory selections, click the button below to scan the selected folders and build the PDF index used by the shortcode.', 'kiss-automated-pdf-linker' ); ?></p>
            <p><?php \esc_html_e( 'You should rebuild the index whenever you add, remove, or rename PDF files in the selected directories.', 'kiss-automated-pdf-linker' ); ?></p>

            <form method="post" action="">
                <?php \wp_nonce_field( 'kapl_rebuild_index_action', 'kapl_rebuild_index_nonce' ); ?>
                <p>
                    <button type="submit" name="kapl_rebuild_index" class="button button-primary">
                        <?php \esc_html_e( 'Rebuild PDF Index Now', 'kiss-automated-pdf-linker' ); ?>
                    </button>
                </p>
            </form>

            <?php $this->render_index_status(); ?>

            <hr>

            <h2><?php \esc_html_e( 'System Self-Tests', 'kiss-automated-pdf-linker' ); ?></h2>
            <p><?php \esc_html_e( 'Run diagnostic tests to verify plugin functionality and catch potential issues.', 'kiss-automated-pdf-linker' ); ?></p>

            <p>
                <strong><?php \esc_html_e( 'Run Diagnostic Tests:', 'kiss-automated-pdf-linker' ); ?></strong>
                <a href="<?php echo \esc_url( \admin_url( 'tools.php?page=kapl-self-test' ) ); ?>" class="button button-secondary">
                    <?php \esc_html_e( 'Link to new page', 'kiss-automated-pdf-linker' ); ?>
                </a>
            </p>
            <p class="description">
                <?php \esc_html_e( 'Click to run comprehensive diagnostic tests. This will verify that all plugin components are working correctly.', 'kiss-automated-pdf-linker' ); ?>
            </p>

        </div>
        <?php
    }

    /**
     * Render current index status.
     *
     * @return void
     */
    private function render_index_status(): void {
        $index_stats = $this->settings->get_index_builder()->get_index_stats();
        
        echo '<p><em>' . \esc_html( $index_stats['message'] ) . '</em></p>';
        
        if ( 'ready' === $index_stats['status'] ) {
            echo '<details>';
            echo '<summary>' . \esc_html__( 'Index Details', 'kiss-automated-pdf-linker' ) . '</summary>';
            
            $validation = $this->settings->get_index_builder()->validate_index();
            if ( $validation['is_valid'] ) {
                echo '<p style="color: green;">✓ ' . \esc_html__( 'Index is valid', 'kiss-automated-pdf-linker' ) . '</p>';
            } else {
                echo '<p style="color: red;">✗ ' . \esc_html__( 'Index has validation errors', 'kiss-automated-pdf-linker' ) . '</p>';
                foreach ( $validation['errors'] as $error ) {
                    echo '<p style="color: red; margin-left: 20px;">• ' . \esc_html( $error ) . '</p>';
                }
            }
            
            echo '</details>';
        }
    }
}
