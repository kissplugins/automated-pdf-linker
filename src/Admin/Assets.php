<?php
/**
 * Admin Assets
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Admin;

/**
 * Admin Assets Class
 *
 * Handles admin-side asset enqueuing.
 *
 * @since 3.0.0
 */
class Assets {

    /**
     * Enqueue admin scripts and styles.
     *
     * @param string $hook Current admin page hook suffix.
     * @return void
     */
    public static function enqueue_scripts( string $hook ): void {
        // Only load on our plugin screen (works for both Tools and Settings contexts)
        /*
         * IMPORTANT: Do NOT refactor this to a strict `settings_page_` prefix check.
         * - Our admin page is registered via add_management_page(), which produces hook ids like `tools_page_{slug}`.
         * - In earlier attempts, checking exclusively for `settings_page_...` caused assets (incl. debug inline JS) not to load.
         * - Using a slug substring match (strpos) is deliberate and resilient: it works for Tools now and will also work if
         *   we ever move the screen under Settings (yielding `settings_page_{slug}`).
         */
        if ( false === strpos( (string) $hook, Settings::SETTINGS_SLUG ) ) {
            return;
        }

        self::enqueue_admin_styles();
        self::enqueue_color_picker();
        if ( self::is_debug_enabled() ) {
            self::enqueue_debug_tools();
        }
    }

    /**
     * Enqueue admin styles.
     *
     * @return void
     */
    private static function enqueue_admin_styles(): void {
        $plugin_url = \plugin_dir_url( dirname( dirname( __DIR__ ) ) . '/kiss-automated-pdf-linker-v3.php' );

        \wp_enqueue_style(
            'kapl-admin-styles',
            $plugin_url . 'assets/admin.css',
            [],
            '3.0.3'
        );
    }

    /**
     * Enqueue color picker assets.
     *
     * @return void
     */
    private static function enqueue_color_picker(): void {
        // Enqueue WordPress color picker
        \wp_enqueue_style( 'wp-color-picker' );
        \wp_enqueue_script( 'wp-color-picker' );

        // Enqueue our custom script to initialize color picker
        \wp_enqueue_script(
            'kapl-admin-script',
            false, // We'll use inline script instead of a separate file
            [ 'wp-color-picker' ],
            '3.0.0',
            true
        );

        // Initialize color picker
        \wp_add_inline_script( 'kapl-admin-script', '
            jQuery(document).ready(function($) {
                $(".kapl-color-picker").wpColorPicker();
            });
        ' );
        }


    /**
     * Whether on-page debug should be enabled.
     */
    private static function is_debug_enabled(): bool {
        // URL toggle has priority
        if ( isset($_GET['kapl_debug']) ) {
            return '1' === (string) $_GET['kapl_debug'];
        }
        // Fallback to saved setting
        $settings = \get_option( Settings::SETTINGS_OPTION_NAME, [] );
        return ! empty( $settings['on_screen_debug'] );
    }

    /**
     * Enqueue on-page debugging helpers (panel + measurements) for the Folder Viewer.
     * Only runs when kapl_debug=1 is present in the URL.
     */
    private static function enqueue_debug_tools(): void {
        // Prepare data about the admin stylesheet.
        $wp_styles = \wp_styles();
        $handle    = 'kapl-admin-styles';
        $registered = isset($wp_styles->registered[$handle]) ? $wp_styles->registered[$handle] : null;

        $plugin_dir = \plugin_dir_path( dirname( dirname( __DIR__ ) ) . '/kiss-automated-pdf-linker-v3.php' );
        $css_path   = $plugin_dir . 'assets/admin.css';
        $css_mtime  = file_exists($css_path) ? (int) filemtime($css_path) : 0;

        // Attach debug data to the existing admin script handle so inline code always prints.
        $data = [
            'style_handle'   => $handle,
            'style_enqueued' => \wp_style_is($handle, 'enqueued'),
            'style_done'     => \wp_style_is($handle, 'done'),
            'style_src'      => $registered ? $registered->src : '',
            'style_ver'      => $registered ? $registered->ver : '',
            'style_mtime'    => $css_mtime,
            'plugin_version' => defined('KAPL_VERSION') ? KAPL_VERSION : 'unknown',
            'wp_debug'       => defined('WP_DEBUG') ? (bool) WP_DEBUG : false,
        ];

        \wp_localize_script('kapl-admin-script', 'KAPL_DEBUG_DATA', $data);

        // Inline styles to visually highlight columns and the debug panel.
        \wp_add_inline_style(
            'kapl-admin-styles',
            '.kapl-debug-panel{margin:10px 0;padding:10px;background:#111;color:#fff;border:1px solid #333;border-radius:6px}' .
            '.kapl-debug-panel code{background:#222;padding:2px 4px;border-radius:3px}' .
            '.kapl-folder-viewer__filename{background:rgba(0,128,255,.06)}' .
            '.kapl-folder-viewer__modified{background:rgba(0,255,128,.06)}' .
            '.kapl-folder-viewer__size{background:rgba(255,128,0,.06)}'
        );

        // Inline script: builds a debug panel and measures column widths / table layout.
        \wp_add_inline_script('kapl-admin-script', "(function(){
            try {
                var container = document.querySelector('.kapl-folder-viewer');
                if(!container){ return; }
                var panel = document.getElementById('kapl-debug-panel');
                if(!panel){
                    panel = document.createElement('div');
                    panel.id = 'kapl-debug-panel';
                    panel.className = 'kapl-debug-panel';
                    container.parentNode.insertBefore(panel, container);
                }

                var table = container.querySelector('.kapl-folder-viewer__table');
                var layout = table ? getComputedStyle(table).getPropertyValue('table-layout') : 'n/a';
                var widths = [];
                var paddings = [];
                var firstRow = table && table.tBodies && table.tBodies[0] ? table.tBodies[0].rows[0] : null;
                if(firstRow){
                    var cells = Array.from(firstRow.cells);
                    cells.forEach(function(td, i){
                        var rect = td.getBoundingClientRect();
                        widths.push(Math.round(rect.width)+'px');
                        var cs = getComputedStyle(td);
                        paddings.push(cs.paddingLeft+' | '+cs.paddingRight);
                    });
                }

                // Detect if our CSS file is present in the DOM
                var linkTags = Array.from(document.querySelectorAll('link[rel=\'stylesheet\']'));
                var cssLink = linkTags.find(function(l){ return (l.href||'').indexOf('assets/admin.css') !== -1; });
                var styleSheetFound = !!cssLink;

                // Also verify via styleSheets API
                var ssMatch = Array.from(document.styleSheets).find(function(s){ return (s.href||'').indexOf('assets/admin.css') !== -1; });

                var html = ''+
                  '<strong>KAPL Folder Viewer Debug</strong><br>'+
                  '<div style=\'margin-top:6px\'>CSS handle: <code>'+KAPL_DEBUG_DATA.style_handle+'</code> | enqueued: <code>'+KAPL_DEBUG_DATA.style_enqueued+'</code> | done: <code>'+KAPL_DEBUG_DATA.style_done+'</code></div>'+
                  '<div>registered src: <code>'+(KAPL_DEBUG_DATA.style_src||'(none)')+'</code> | ver: <code>'+(KAPL_DEBUG_DATA.style_ver||'(none)')+'</code> | filemtime: <code>'+KAPL_DEBUG_DATA.style_mtime+'</code></div>'+
                  '<div>DOM stylesheet link present: <code>'+styleSheetFound+'</code> | styleSheets match: <code>'+!!ssMatch+'</code></div>'+
                  '<div>Plugin version: <code>'+KAPL_DEBUG_DATA.plugin_version+'</code> | WP_DEBUG: <code>'+KAPL_DEBUG_DATA.wp_debug+'</code></div>'+
                  '<div>Table layout: <code>'+layout+'</code></div>'+
                  (widths.length ? '<div>First row cell widths: <code>'+widths.join(' , ')+'</code></div>' : '<div>No rows found to measure.</div>')+
                  (paddings.length ? '<div>First row cell paddings (L | R): <code>'+paddings.join(' , ')+'</code></div>' : '');

                // Hinting
                var hint = '';
                if(layout === 'auto' && widths.length >= 3){
                    var w1 = parseInt(widths[0]); var w2 = parseInt(widths[1]); var w3 = parseInt(widths[2]);
                    if(w1 > (w2+w3)){
                        hint = 'The File Name column dominates width with table-layout:auto. Consider table-layout: fixed; or a <colgroup> to enforce widths.';
                    }
                }
                if(hint){ html += '<div style=\'margin-top:6px;color:#ffcc00\'><em>Hint:</em> '+hint+'</div>'; }

                panel.innerHTML = html;
            } catch(e) { console && console.warn && console.warn('KAPL debug init error', e); }
        })();");
    }

}
