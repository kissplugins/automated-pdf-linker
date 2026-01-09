<?php
/**
 * File Listing Viewer (WP-friendly component)
 *
 * Provides a flat, searchable and sortable table with fuzzy search and date filter.
 *
 * @package KissPlugins\AutomatedPdfLinker\Admin\Components
 * @since 3.1.5
 */

namespace KissPlugins\AutomatedPdfLinker\Admin\Components;

if ( ! defined( 'ABSPATH' ) ) { exit; }

class FileListingViewer {
    /**
     * Render the viewer
     *
     * @param array $files   Array of ['name' => string, 'size' => int bytes, 'modified' => ISO8601 string]
     * @param array $options Options: id, show_debug(bool), date_format(string)
     */
    public static function render( array $files, array $options = [] ): void {
        $defaults = [
            'id'          => 'kapl-file-viewer-' . \wp_generate_uuid4(),
            'show_debug'  => false,
            'date_format' => \get_option( 'date_format' ) . ' ' . \get_option( 'time_format' ),
        ];
        $opts = array_merge( $defaults, $options );

        // Sanitize files minimally to ensure expected keys exist
        $normalized = [];
        foreach ( $files as $f ) {
            $name     = isset( $f['name'] ) ? (string) $f['name'] : '';
            $name     = \sanitize_text_field( \wp_check_invalid_utf8( $name, true ) );
            $size     = isset( $f['size'] ) ? (int) $f['size'] : 0;
            $modified = isset( $f['modified'] ) ? (string) $f['modified'] : date( 'c', 0 );
            $url      = isset( $f['url'] ) ? \esc_url_raw( (string) $f['url'] ) : '';
            $normalized[] = [
                'name'     => $name,
                'size'     => $size,
                'modified' => $modified,
                'url'      => $url,
            ];
        }

        // Output
        ?>
        <div id="<?php echo \esc_attr( $opts['id'] ); ?>" class="kapl-flv">
            <style>
                .kapl-flv{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,Oxygen,Ubuntu,sans-serif;background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:16px;margin:16px 0}
                .kapl-flv__controls{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:10px}
                .kapl-flv__controls input{padding:8px;border:1px solid #ddd;border-radius:4px;font-size:13px;min-width:180px}
                .kapl-flv__btn{padding:8px 12px;border:1px solid #ddd;border-radius:4px;background:#f6f7f7;cursor:pointer}
                .kapl-flv__btn:hover{background:#eee}
                .kapl-flv__stats{color:#555;margin:6px 0 10px}
                .kapl-flv__table{width:100%;border-collapse:collapse;font-size:13px}
                .kapl-flv__table th,.kapl-flv__table td{padding:10px;border-bottom:1px solid #f0f0f0;text-align:left}
                .kapl-flv__table th{background:#f8f9fa;position:sticky;top:0}
                .kapl-flv__th--sortable{cursor:pointer;user-select:none}
                .kapl-flv__th--sortable:after{content:'⇅';opacity:.3;margin-left:6px}
                .kapl-flv__th--asc:after{content:'↑';opacity:1}
                .kapl-flv__th--desc:after{content:'↓';opacity:1}
                .kapl-flv__rownum{color:#999;width:44px}
                .kapl-flv__size{white-space:nowrap}
                .kapl-flv__name{font-weight:500;word-break:break-word}
                .kapl-flv__nores{padding:24px;text-align:center;color:#666}
                .kapl-flv__hl{background:yellow;font-weight:600}
                .kapl-flv__debug{display:none;margin-top:12px;padding:12px;background:#f8f9fa;border:1px solid #e4e5e7;border-radius:4px;font-family:Menlo,Consolas,monospace;font-size:12px}
            </style>

            <div class="kapl-flv__controls">
                <input type="text" id="<?php echo \esc_attr( $opts['id'] ); ?>-q" placeholder="<?php echo \esc_attr__( 'Search files (fuzzy)', 'kiss-automated-pdf-linker' ); ?>">
                <input type="date" id="<?php echo \esc_attr( $opts['id'] ); ?>-date" placeholder="<?php echo \esc_attr__( 'Filter by date', 'kiss-automated-pdf-linker' ); ?>">
                <button type="button" class="kapl-flv__btn" id="<?php echo \esc_attr( $opts['id'] ); ?>-clear"><?php echo \esc_html__( 'Clear Filters', 'kiss-automated-pdf-linker' ); ?></button>
                <?php if ( $opts['show_debug'] ) : ?>
                    <button type="button" class="kapl-flv__btn" id="<?php echo \esc_attr( $opts['id'] ); ?>-dbg-toggle"><?php echo \esc_html__( 'Toggle Debug', 'kiss-automated-pdf-linker' ); ?></button>
                <?php endif; ?>
            </div>

            <div class="kapl-flv__stats" id="<?php echo esc_attr( $opts['id'] ); ?>-stats"></div>

            <div class="kapl-flv__tablewrap">
                <table class="kapl-flv__table" id="<?php echo esc_attr( $opts['id'] ); ?>-tbl">
                    <thead>
                        <tr>
                            <th class="kapl-flv__rownum">#</th>
                            <th class="kapl-flv__th--sortable" data-sort="name"><?php echo \esc_html__( 'Filename', 'kiss-automated-pdf-linker' ); ?></th>
                            <th class="kapl-flv__th--sortable" data-sort="modified"><?php echo \esc_html__( 'Modified Date', 'kiss-automated-pdf-linker' ); ?></th>
                            <th class="kapl-flv__th--sortable" data-sort="size"><?php echo \esc_html__( 'File Size', 'kiss-automated-pdf-linker' ); ?></th>
                        </tr>
                    </thead>
                    <tbody id="<?php echo esc_attr( $opts['id'] ); ?>-tbody">
                            <?php if ( empty( $normalized ) ) : ?>
                                <tr><td colspan="4" class="kapl-flv__nores"><?php echo \esc_html__( 'No files found matching your criteria', 'kiss-automated-pdf-linker' ); ?></td></tr>
                            <?php else : ?>
                                <?php foreach ( $normalized as $i => $f ) : ?>
                                    <tr>
                                        <td class="kapl-flv__rownum"><?php echo intval( $i + 1 ); ?></td>
                                        <td class="kapl-flv__name">
                                            <?php if ( ! empty( $f['url'] ) ) : ?>
                                                <a href="<?php echo esc_url( $f['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo \esc_html( $f['name'] ); ?></a>
                                            <?php else : ?>
                                                <?php echo \esc_html( $f['name'] ); ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo \esc_html( \date_i18n( $opts['date_format'], strtotime( $f['modified'] ) ) ); ?></td>
                                        <td class="kapl-flv__size"><?php echo \esc_html( \size_format( max( 0, (int) $f['size'] ) ) ); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                        <noscript><div class="kapl-flv__nores"><?php echo \esc_html__( 'JavaScript is required to view and filter this list.', 'kiss-automated-pdf-linker' ); ?></div></noscript>
                </table>
            </div>

            <?php if ( $opts['show_debug'] ) : ?>
                <div class="kapl-flv__debug" id="<?php echo esc_attr( $opts['id'] ); ?>-dbg">
                    <div><strong><?php echo \esc_html__( 'Search:', 'kiss-automated-pdf-linker' ); ?></strong> <span id="<?php echo \esc_attr( $opts['id'] ); ?>-dbg-q">-</span></div>
                    <div><strong><?php echo \esc_html__( 'Date:', 'kiss-automated-pdf-linker' ); ?></strong> <span id="<?php echo \esc_attr( $opts['id'] ); ?>-dbg-date">-</span></div>
                    <div><strong><?php echo \esc_html__( 'Sort:', 'kiss-automated-pdf-linker' ); ?></strong> <span id="<?php echo \esc_attr( $opts['id'] ); ?>-dbg-sort">-</span></div>
                    <div><strong><?php echo \esc_html__( 'Dir:', 'kiss-automated-pdf-linker' ); ?></strong> <span id="<?php echo \esc_attr( $opts['id'] ); ?>-dbg-dir">-</span></div>
                    <div><strong><?php echo \esc_html__( 'Counts:', 'kiss-automated-pdf-linker' ); ?></strong> <span id="<?php echo \esc_attr( $opts['id'] ); ?>-dbg-counts">-</span></div>
                </div>
            <?php endif; ?>

            <script>
            (function(){
                try {
                const id = <?php echo \wp_json_encode( $opts['id'] ); ?>;
                const filesData = <?php echo \wp_json_encode( $normalized ); ?>;
                console.debug('KAPL FileListingViewer init', { id, isArray: Array.isArray(filesData), count: Array.isArray(filesData) ? filesData.length : 0 });
                // Signal early that the script is running
                const tbInit = document.querySelector(`#${id}-tbody`);
                if (tbInit) {
                    tbInit.innerHTML = `<tr><td colspan=\"4\" class=\"kapl-flv__nores\"><?php echo \esc_html__( 'Initializing…', 'kiss-automated-pdf-linker' ); ?></td></tr>`;
                }

                const qs = (sel) => document.querySelector(sel);
                const qsa = (sel) => Array.from(document.querySelectorAll(sel));

                class FLV {
                    constructor(){
                        const src = Array.isArray(filesData) ? filesData : [];
                        this.files = src.slice();
                        this.filtered = src.slice();
                        this.sortCol = null; // 'name' | 'modified' | 'size'
                        this.sortDir = null; // 'asc'|'desc'
                        this.q = '';
                        this.date = '';
                        this.bind();
                        this.render();
                        this.updateStats();
                        this.updateDebug();
                    }
                    bind(){
                        const qEl = qs(`#${id}-q`); if(qEl){ qEl.addEventListener('input', e => { this.q = e.target.value || ''; this.applyFilters(); }); }
                        const dEl = qs(`#${id}-date`); if(dEl){ dEl.addEventListener('change', e => { this.date = e.target.value || ''; this.applyFilters(); }); }
                        const cEl = qs(`#${id}-clear`); if(cEl){ cEl.addEventListener('click', () => { this.q=''; this.date=''; const q=qs(`#${id}-q`); const d=qs(`#${id}-date`); if(q) q.value=''; if(d) d.value=''; this.applyFilters(); }); }
                        qsa(`#${id}-tbl th[data-sort]`).forEach(th => th.addEventListener('click', ()=>{ this.sort(th.dataset.sort); }));
                        const dbgBtn = qs(`#${id}-dbg-toggle`); if(dbgBtn){ dbgBtn.addEventListener('click',()=>{ const el=qs(`#${id}-dbg`); if(el){ el.style.display = (el.style.display==='block'?'none':'block'); } }); }
                    }
                    // Match if all tokens from query exist as contiguous substrings (case-insensitive)
                    tokens(q){ return (q||'').trim().toLowerCase().split(/\s+/).filter(Boolean); }
                    matchesAllTokens(text, q){
                        const hay = (text||'').toLowerCase();
                        const toks = this.tokens(q);
                        if (toks.length===0) return true;
                        return toks.every(t => hay.indexOf(t) !== -1);
                    }
                    escapeRe(s){ return s.replace(/[.*+?^${}()|[\]\\]/g,'\\$&'); }
                    applyFilters(){
                        this.filtered = this.files.filter(f => {
                            if(this.q && !this.matchesAllTokens(f.name, this.q)) return false;
                            if(this.date){
                                const d = new Date(f.modified).toISOString().split('T')[0];
                                if(d !== this.date) return false;
                            }
                            return true;
                        });
                        this.render(); this.updateStats(); this.updateDebug();
                    }
                    sort(col){
                        if(this.sortCol===col){ this.sortDir = (this.sortDir==='asc'?'desc':'asc'); } else { this.sortCol=col; this.sortDir='asc'; }
                        const toVal = (f)=>{
                            if(col==='size') return parseInt(f.size||0,10);
                            if(col==='modified') return (new Date(f.modified)).getTime();
                            return (''+(f[col]||'')).toLowerCase();
                        };
                        this.files.sort((a,b)=>{
                            const av=toVal(a), bv=toVal(b);
                            if(av<bv) return this.sortDir==='asc'?-1:1;
                            if(av>bv) return this.sortDir==='asc'?1:-1;
                            return 0;
                        });
                        this.applyFilters();
                        qsa(`#${id}-tbl th[data-sort]`).forEach(th=>{ th.classList.remove('kapl-flv__th--asc','kapl-flv__th--desc'); if(th.dataset.sort===this.sortCol){ th.classList.add(this.sortDir==='asc'?'kapl-flv__th--asc':'kapl-flv__th--desc'); } });
                    }
                    sizeFmt(bytes){ if(!bytes) return '0 B'; const k=1024,s=['B','KB','MB','GB','TB']; const i=Math.floor(Math.log(bytes)/Math.log(k)); return (bytes/Math.pow(k,i)).toFixed(2)+' '+s[i]; }
                    dateFmt(iso){ const d=new Date(iso); return d.toLocaleString(); }
                    highlight(name, q){ if(!q) return name; let out = (name||''); const toks=this.tokens(q); toks.forEach(t=>{ const re=new RegExp(this.escapeRe(t),'gi'); out = out.replace(re,'<span class="kapl-flv__hl">$&</span>'); }); return out; }
                    render(){
                        const tb = qs(`#${id}-tbody`);
                        if(!tb){ return; }
                        if(this.filtered.length===0){ tb.innerHTML = `<tr><td colspan=\"4\" class=\"kapl-flv__nores\"><?php echo \esc_html__( 'No files found matching your criteria', 'kiss-automated-pdf-linker' ); ?></td></tr>`; return; }
                        tb.innerHTML = this.filtered.map((f,i)=>{
                            const nameHtml = f.url ? `<a href=\"${f.url}\" target=\"_blank\" rel=\"noopener noreferrer\">${this.highlight(f.name,this.q)}</a>` : this.highlight(f.name,this.q);
                            return `<tr><td class=\"kapl-flv__rownum\">${i+1}</td><td class=\"kapl-flv__name\">${nameHtml}</td><td>${this.dateFmt(f.modified)}</td><td class=\"kapl-flv__size\">${this.sizeFmt(parseInt(f.size||0,10))}</td></tr>`;
                        }).join('');
                    }
                    updateStats(){ const total=this.files.length, vis=this.filtered.length; const el=qs(`#${id}-stats`); el.textContent = (this.q||this.date) ? `<?php echo \esc_js( \__( 'Showing', 'kiss-automated-pdf-linker' ) ); ?> ${vis} <?php echo \esc_js( \__( 'of', 'kiss-automated-pdf-linker' ) ); ?> ${total} <?php echo \esc_js( \__( 'files (filtered)', 'kiss-automated-pdf-linker' ) ); ?>` : `<?php echo \esc_js( \__( 'Showing all', 'kiss-automated-pdf-linker' ) ); ?> ${total} <?php echo \esc_js( \__( 'files', 'kiss-automated-pdf-linker' ) ); ?>`; }
                    updateDebug(){ const dbg=qs(`#${id}-dbg`); if(!dbg) return; qs(`#${id}-dbg-q`).textContent=this.q||'-'; qs(`#${id}-dbg-date`).textContent=this.date||'-'; qs(`#${id}-dbg-sort`).textContent=(this.sortCol?`${this.sortCol} ${this.sortDir||''}`:'-'); qs(`#${id}-dbg-dir`).textContent=window.location.pathname; qs(`#${id}-dbg-counts`).textContent=`${this.filtered.length}/${this.files.length}`; }
                }
                try {
                    window.kaplFlv = new FLV();
                    console.debug('KAPL FileListingViewer ready', { total: window.kaplFlv.files.length });
                } catch (e) {
                    console.error('KAPL FileListingViewer init error', e);
                    const tb = document.querySelector(`#${id}-tbody`);
                    if (tb) {
                        tb.innerHTML = `<tr><td colspan=\"4\" class=\"kapl-flv__nores\"><?php echo \esc_html__( 'Viewer error', 'kiss-automated-pdf-linker' ); ?>: ${e && e.message ? e.message : 'Unknown'}</td></tr>`;
                    }
                }
            } catch (e) {
                const fallbackId = <?php echo \wp_json_encode( $opts['id'] ); ?>;
                console.error('KAPL FileListingViewer top-level error', e);
                const tb = document.querySelector(`#${fallbackId}-tbody`);
                if (tb) { tb.innerHTML = `<tr><td colspan=\"4\" class=\"kapl-flv__nores\"><?php echo \esc_html__( 'Viewer error', 'kiss-automated-pdf-linker' ); ?>: ${e && e.message ? e.message : 'Unknown'}</td></tr>`; }
            }
            })();
            </script>
        </div>
        <?php
    }
}

