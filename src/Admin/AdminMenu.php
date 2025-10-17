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
        /*
         * IMPORTANT: This settings screen is registered under Tools via add_management_page().
         * That produces an admin hook like `tools_page_{slug}` rather than `settings_page_{slug}`.
         * The Assets::enqueue_scripts() guard uses a slug substring match to support both contexts
         * in case this screen ever moves. Do not change registration context unless you also
         * update the asset loader accordingly.
         */

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

        // Check for index migration (e.g., adding new metadata fields)
        $this->settings->check_and_perform_migration();

        // Handle index rebuilding action
        $this->settings->handle_index_rebuild();

        ?>
        <div class="wrap">
            <h1><?php echo \esc_html( \get_admin_page_title() ); ?></h1>

            <?php \settings_errors( 'kapl_rebuild_status' ); ?>
            <?php \settings_errors( 'kapl_migration_status' ); ?>

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

            <?php $this->render_folder_file_listing_viewer(); ?>

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

    /**
     * Render the folder file listing viewer.
     *
     * @return void
     */
    private function render_folder_file_listing_viewer(): void {
        $settings = $this->settings->get_settings();
        $selected_directories = $settings['selected_directories'] ?? [];

        echo '<h2>' . \esc_html__( 'Folder File Listing Viewer', 'kiss-automated-pdf-linker' ) . '</h2>';
        echo '<p>' . \esc_html__( 'Review the PDF files that were discovered in each selected folder. Click a file name to open it in a new tab.', 'kiss-automated-pdf-linker' ) . '</p>';


        $kapl_debug_enabled = ( isset($_GET['kapl_debug']) && '1' === (string) $_GET['kapl_debug'] );
        if ( ! $kapl_debug_enabled ) {
            $kapl_settings = get_option( \KissPlugins\AutomatedPdfLinker\Admin\Settings::SETTINGS_OPTION_NAME, [] );
            $kapl_debug_enabled = ! empty( $kapl_settings['on_screen_debug'] );
        }
        $kapl_debug_toggle_url = esc_url( add_query_arg( 'kapl_debug', $kapl_debug_enabled ? '0' : '1' ) );
        echo '<p><a href="' . $kapl_debug_toggle_url . '" class="button button-small">' . ( $kapl_debug_enabled ? esc_html__( 'Disable Debug', 'kiss-automated-pdf-linker' ) : esc_html__( 'Enable Debug', 'kiss-automated-pdf-linker' ) ) . '</a></p>';
        if ( $kapl_debug_enabled ) {
            echo '<div id="kapl-debug-panel" class="kapl-debug-panel" aria-live="polite"></div>';
        }

        if ( empty( $selected_directories ) ) {
            echo '<p><em>' . \esc_html__( 'No folders have been selected for scanning yet. Choose folders above and rebuild the index to see their contents.', 'kiss-automated-pdf-linker' ) . '</em></p>';
            return;
        }

        $grouped_files = $this->settings->get_index_builder()->get_index_by_directory( $selected_directories );

        if ( empty( $grouped_files ) ) {
            echo '<p><em>' . \esc_html__( 'The index does not contain any PDFs yet. Rebuild the index after selecting folders to populate this viewer.', 'kiss-automated-pdf-linker' ) . '</em></p>';
            return;
        }

        // DRY: Re-use IndexBuilder::get_index_stats() so the count matches the status panel and rebuild message.
        $index_stats = $this->settings->get_index_builder()->get_index_stats();
        $total_files_count = isset( $index_stats['total_files'] ) ? (int) $index_stats['total_files'] : 0;

        $upload_dir_info = wp_upload_dir();
        $base_url = trailingslashit( $upload_dir_info['baseurl'] );

        echo '<div class="kapl-folder-viewer">';
            // Show total number of files at the top of the list.
            echo '<p class="kapl-folder-viewer__total"><strong>' . sprintf( \esc_html__( 'Total files: %d', 'kiss-automated-pdf-linker' ), (int) $total_files_count ) . '</strong></p>';

            // Phase 1 controls: search, date filter, and sort toggles (client-side only)
            echo '<div class="kapl-folder-viewer__controls">'
                . '<label class="kapl-c-label" for="kapl-filter-q">' . \esc_html__( 'Search', 'kiss-automated-pdf-linker' ) . '</label>'
                . '<input type="text" id="kapl-filter-q" class="regular-text" placeholder="' . \esc_attr__( 'filename, path or folder', 'kiss-automated-pdf-linker' ) . '" />'
                . '<label class="kapl-c-label" for="kapl-filter-date">' . \esc_html__( 'Date', 'kiss-automated-pdf-linker' ) . '</label>'
                . '<input type="text" id="kapl-filter-date" class="regular-text" placeholder="' . \esc_attr__( 'YYYY-MM-DD or 10-17-25', 'kiss-automated-pdf-linker' ) . '" />'
                . '<span class="kapl-filter-sep">|</span>'
                . '<div class="kapl-folder-viewer__sort">'
                    . '<button type="button" class="button kapl-sort" data-key="modified" aria-pressed="false">' . \esc_html__( 'Sort by Date', 'kiss-automated-pdf-linker' ) . '</button>'
                    . '<button type="button" class="button kapl-sort" data-key="name" aria-pressed="false">' . \esc_html__( 'Sort by Name', 'kiss-automated-pdf-linker' ) . '</button>'
                . '</div>'
                . '<div id="kapl-filter-count" class="kapl-filter-count" aria-live="polite" data-total="' . (int) $total_files_count . '">'
                    . sprintf( \esc_html__( 'Showing %1$d of %2$d', 'kiss-automated-pdf-linker' ), (int) $total_files_count, (int) $total_files_count )
                . '</div>'
            . '</div>';

        // Global, cross-folder running row number starting at 1 (requested UI)
        $row_number = 1;

        foreach ( $selected_directories as $directory ) {
            $files = $grouped_files[ $directory ] ?? [];

            echo '<details class="kapl-folder-viewer__folder" open>';
            echo '<summary>' . \esc_html( $directory ) . '/</summary>';

            if ( empty( $files ) ) {
                echo '<p class="kapl-folder-viewer__empty">' . \esc_html__( 'No PDF files found in this folder.', 'kiss-automated-pdf-linker' ) . '</p>';
                echo '</details>';
                continue;
            }

            echo '<div class="kapl-folder-viewer__table-wrapper">';
            echo '<table class="kapl-folder-viewer__table"' . ( $kapl_debug_enabled ? ' data-kapl-debug="1"' : '' ) . '>';
            echo '<thead>';
            echo '<tr>';
            echo '<th scope="col">' . \esc_html__( 'File Name', 'kiss-automated-pdf-linker' ) . '</th>';
            echo '<th scope="col">' . \esc_html__( 'Modified Date', 'kiss-automated-pdf-linker' ) . '</th>';
            echo '<th scope="col">' . \esc_html__( 'File Size (KB)', 'kiss-automated-pdf-linker' ) . '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            foreach ( $files as $file ) {
                $relative_path = isset( $file['path'] ) ? (string) $file['path'] : '';
                $file_name = isset( $file['filename'] ) ? (string) $file['filename'] : basename( $relative_path );

                if ( '' === $relative_path ) {
                    continue;
                }

                $file_url = $base_url . ltrim( $relative_path, '/' );
                $folder_prefix = $directory . '/';
                $display_path = $relative_path;
                $modified_display = '&mdash;';
                $size_display = '&mdash;';

                if ( 0 === strpos( $relative_path, $folder_prefix ) ) {
                    $display_path = substr( $relative_path, strlen( $folder_prefix ) );
                }

                if ( isset( $file['modified'] ) && is_numeric( $file['modified'] ) ) {
                    $modified_display = \esc_html( \date_i18n( \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ), (int) $file['modified'] ) );
                }

                if ( isset( $file['size_bytes'] ) && is_numeric( $file['size_bytes'] ) ) {
                    $kilobytes = (int) $file['size_bytes'] / 1024;
                    $size_display = \esc_html( number_format_i18n( max( $kilobytes, 0 ), 1 ) );
                }

                echo '<tr class="kapl-folder-viewer__file"'
                    . ' data-filename="' . \esc_attr( (string) $file_name ) . '"'
                    . ' data-folder="' . \esc_attr( (string) $directory ) . '"'
                    . ' data-path="' . \esc_attr( (string) $relative_path ) . '"'
                    . ' data-modified="' . \esc_attr( isset($file['modified']) && is_numeric($file['modified']) ? (int) $file['modified'] : 0 ) . '"'
                    . ' data-size="' . \esc_attr( isset($file['size_bytes']) && is_numeric($file['size_bytes']) ? (int) $file['size_bytes'] : 0 ) . '"'
                    . '>';
                echo '<td class="kapl-folder-viewer__filename">';
                // Prepend running row number like "1.) " before the filename link (does not reset per folder)
                echo '<span class="kapl-rownum">' . (int) $row_number . '.) </span>';
                echo '<a href="' . esc_url( $file_url ) . '" target="_blank" rel="noopener noreferrer">' . esc_html( $file_name ) . '</a>';

                if ( '' !== $display_path && $display_path !== $file_name ) {
                    echo '<span class="kapl-folder-viewer__path">' . esc_html( $display_path ) . '</span>';
                }

                echo '</td>';
                echo '<td class="kapl-folder-viewer__modified">' . $modified_display . '</td>';
                // Increment global row number after rendering this row
                $row_number++;

                echo '<td class="kapl-folder-viewer__size">' . $size_display . '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            echo '</div>';
            echo '</details>';
        }

        echo '</div>';
    }
}
