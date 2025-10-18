<?php
/**
 * File Listing Page (Flat, searchable/sortable)
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.1.5
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

use KissPlugins\AutomatedPdfLinker\Core\Plugin;

class FileListingPage {

    /**
     * Page slug.
     */
    const PAGE_SLUG = 'kapl-file-listing';

    /** @var Plugin */
    private $plugin;

    public function __construct( Plugin $plugin ) {
        $this->plugin = $plugin;
    }

    /**
     * Register admin menu item under Tools.
     */
    public function add_admin_menu(): void {
        \add_management_page(
            \__( 'KISS PDF Linker File Listing', 'kiss-automated-pdf-linker' ),
            \__( 'KISS PDF Linker File Listing', 'kiss-automated-pdf-linker' ),
            'manage_options',
            self::PAGE_SLUG,
            [ $this, 'render_page' ]
        );
    }

    /**
     * Render the page using the universal viewer.
     */
    public function render_page(): void {
        if ( ! \current_user_can( 'manage_options' ) ) {
            \wp_die( \esc_html__( 'You do not have sufficient permissions to access this page.', 'kiss-automated-pdf-linker' ) );
            return;
        }

        // Allow rebuild action via POST (reuse Settings logic for DRY and consistent notices)
        $this->plugin->get_settings()->handle_index_rebuild();

        // Build data from the existing (possibly just rebuilt) index

        // Build data from the existing index (DRY: reuse IndexBuilder)
        $index = $this->plugin->get_index_builder()->get_index() ?: [];
        $files = [];
        $upload_info = \wp_upload_dir();
        $baseurl = isset($upload_info['baseurl']) ? trailingslashit($upload_info['baseurl']) : '';
        foreach ( $index as $item ) {
            $filename     = isset($item['filename']) ? (string) $item['filename'] : ( isset($item['path']) ? basename((string)$item['path']) : '' );
            $modified_ts  = isset($item['modified']) && is_numeric($item['modified']) ? (int) $item['modified'] : null;
            $modified_iso = $modified_ts ? date('c', $modified_ts) : date('c', 0);
            $size_bytes   = isset($item['size_bytes']) && is_numeric($item['size_bytes']) ? (int) $item['size_bytes'] : 0;
            $rel_path     = isset($item['path']) ? ltrim((string)$item['path'], '/\\') : '';
            $url          = $baseurl && $rel_path ? $baseurl . $rel_path : '';
            $files[] = [ 'name' => $filename, 'size' => $size_bytes, 'modified' => $modified_iso, 'url' => $url ];
        }

        // Determine debug toggle (re-use same logic as Settings page)
        $kapl_debug_enabled = ( isset($_GET['kapl_debug']) && '1' === (string) $_GET['kapl_debug'] );
        if ( ! $kapl_debug_enabled ) {
            $kapl_settings = get_option( \KissPlugins\AutomatedPdfLinker\Admin\Settings::SETTINGS_OPTION_NAME, [] );
            $kapl_debug_enabled = ! empty( $kapl_settings['on_screen_debug'] );
        }
        $kapl_debug_toggle_url = esc_url( add_query_arg( 'kapl_debug', $kapl_debug_enabled ? '0' : '1' ) );
        if ( $kapl_debug_enabled ) {
            echo '<div class="notice notice-info"><p>'
                . 'Index items: ' . intval( is_array($index) ? count($index) : 0 ) . ' &mdash; '
                . 'Files array: ' . intval( count($files) )
                . '</p></div>';
        }


        echo '<div class="wrap">';
        echo '<h1>' . esc_html__( 'KISS PDF Linker File Listing', 'kiss-automated-pdf-linker' ) . '</h1>';
        // Show rebuild notices, if any
        \settings_errors( 'kapl_rebuild_status' );
        echo '<p>' . esc_html__( 'Search by fuzzy filename or filter by date; click column headers to sort.', 'kiss-automated-pdf-linker' ) . '</p>';
        echo '<p style="display:flex; gap:10px; justify-content:space-between; align-items:center;">'
            . '<span>'
            . '<a href="' . $kapl_debug_toggle_url . '" class="button button-small">' . ( $kapl_debug_enabled ? esc_html__( 'Disable Debug', 'kiss-automated-pdf-linker' ) : esc_html__( 'Enable Debug', 'kiss-automated-pdf-linker' ) ) . '</a>'
            . '</span>'
            . '<span style="margin-left:auto;">'
            . '<form method="post" action="" style="display:inline-block;">'
            . wp_nonce_field( 'kapl_rebuild_index_action', 'kapl_rebuild_index_nonce', true, false )
            . '<input type="hidden" name="kapl_rebuild_index" value="1" />'
            . '<button type="submit" class="button button-primary">' . esc_html__( 'Rebuild PDF Index Now', 'kiss-automated-pdf-linker' ) . '</button>'
            . '</form>'
            . '</span>'
            . '</p>';

        if ( empty( $files ) ) {
            $settings_url = esc_url( admin_url( 'tools.php?page=' . \KissPlugins\AutomatedPdfLinker\Admin\Settings::SETTINGS_SLUG ) );
            echo '<div class="notice notice-info"><p>'
                . esc_html__( 'No indexed files found. To populate this list, select folders and rebuild the index on the settings page.', 'kiss-automated-pdf-linker' )
                . ' <a href="' . $settings_url . '" class="button button-secondary">' . esc_html__( 'Go to Settings', 'kiss-automated-pdf-linker' ) . '</a>'
                . '</p></div>';
        }

        \KissPlugins\AutomatedPdfLinker\Admin\Components\FileListingViewer::render(
            $files,
            [
                'id'          => 'kapl-file-list',
                'show_debug'  => (bool) $kapl_debug_enabled,
                'date_format' => get_option('date_format') . ' ' . get_option('time_format'),
            ]
        );

        echo '</div>';
    }
}

