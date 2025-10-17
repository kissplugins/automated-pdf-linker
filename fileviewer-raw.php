<?php
/**
 * Universal File Listing Viewer for WordPress
 * 
 * Usage: Include this file and call render_file_listing_viewer($data_source)
 * 
 * @param array|callable $data_source - Can be:
 *   - Array of file objects with 'name', 'size', 'modified' properties
 *   - Callable function that returns array of files
 *   - String path to directory (will scan directory)
 *   - 'demo' for demo data
 */

// Prevent direct access
if (!defined('ABSPATH') && !defined('STANDALONE_MODE')) {
    define('STANDALONE_MODE', true);
}

/**
 * Main function to render the file listing viewer
 */
function render_file_listing_viewer($data_source = 'demo', $options = []) {
    $default_options = [
        'id' => 'file-viewer-' . uniqid(),
        'show_debug' => true,
        'allow_download' => false,
        'date_format' => 'Y-m-d H:i:s'
    ];
    
    $options = array_merge($default_options, $options);
    $files = get_file_data($data_source, $options);
    
    ?>
    <div id="<?php echo esc_attr($options['id']); ?>" class="file-listing-viewer">
        <style>
            .file-listing-viewer {
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif;
                max-width: 100%;
                margin: 20px 0;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                padding: 20px;
            }
            
            .flv-header {
                margin-bottom: 20px;
            }
            
            .flv-search-container {
                display: flex;
                gap: 10px;
                margin-bottom: 15px;
                flex-wrap: wrap;
            }
            
            .flv-search-input, .flv-date-input {
                padding: 10px;
                border: 1px solid #ddd;
                border-radius: 4px;
                font-size: 14px;
                flex: 1;
                min-width: 200px;
            }
            
            .flv-clear-filters {
                padding: 10px 20px;
                background: #f0f0f0;
                border: 1px solid #ddd;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                transition: background 0.3s;
            }
            
            .flv-clear-filters:hover {
                background: #e0e0e0;
            }
            
            .flv-stats {
                color: #666;
                font-size: 14px;
                margin-bottom: 10px;
            }
            
            .flv-table-container {
                overflow-x: auto;
                border: 1px solid #e0e0e0;
                border-radius: 4px;
            }
            
            .flv-table {
                width: 100%;
                border-collapse: collapse;
                font-size: 14px;
            }
            
            .flv-table th {
                background: #f5f5f5;
                padding: 12px;
                text-align: left;
                font-weight: 600;
                border-bottom: 2px solid #e0e0e0;
                user-select: none;
                position: sticky;
                top: 0;
                z-index: 10;
            }
            
            .flv-table th.sortable {
                cursor: pointer;
                position: relative;
                padding-right: 25px;
            }
            
            .flv-table th.sortable:hover {
                background: #ebebeb;
            }
            
            .flv-table th.sortable::after {
                content: '⇅';
                position: absolute;
                right: 8px;
                opacity: 0.3;
            }
            
            .flv-table th.sort-asc::after {
                content: '↑';
                opacity: 1;
            }
            
            .flv-table th.sort-desc::after {
                content: '↓';
                opacity: 1;
            }
            
            .flv-table td {
                padding: 10px 12px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .flv-table tbody tr:hover {
                background: #f9f9f9;
            }
            
            .flv-table tbody tr.filtered-out {
                display: none;
            }
            
            .flv-table .row-number {
                color: #999;
                font-size: 12px;
                width: 50px;
            }
            
            .flv-table .file-size {
                white-space: nowrap;
            }
            
            .flv-table .file-name {
                font-weight: 500;
                word-break: break-word;
            }
            
            .flv-highlight {
                background: yellow;
                font-weight: bold;
            }
            
            .flv-debug-panel {
                margin-top: 20px;
                padding: 15px;
                background: #f8f9fa;
                border: 1px solid #dee2e6;
                border-radius: 4px;
                font-family: 'Courier New', monospace;
                font-size: 12px;
            }
            
            .flv-debug-panel h4 {
                margin: 0 0 10px 0;
                font-size: 14px;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            }
            
            .flv-debug-item {
                margin: 5px 0;
                padding: 5px;
                background: white;
                border-left: 3px solid #007cba;
                display: flex;
                align-items: center;
            }
            
            .flv-debug-label {
                font-weight: bold;
                margin-right: 10px;
                min-width: 120px;
            }
            
            .flv-debug-value {
                color: #0073aa;
            }
            
            .flv-no-results {
                text-align: center;
                padding: 40px;
                color: #666;
                font-size: 16px;
            }
            
            .flv-toggle-debug {
                margin-top: 10px;
                padding: 8px 15px;
                background: #007cba;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 13px;
            }
            
            .flv-toggle-debug:hover {
                background: #005a87;
            }
        </style>
        
        <div class="flv-header">
            <div class="flv-search-container">
                <input 
                    type="text" 
                    class="flv-search-input" 
                    placeholder="Search files (fuzzy matching)..."
                    id="<?php echo esc_attr($options['id']); ?>-search"
                >
                <input 
                    type="date" 
                    class="flv-date-input" 
                    placeholder="Filter by date"
                    id="<?php echo esc_attr($options['id']); ?>-date"
                >
                <button class="flv-clear-filters" id="<?php echo esc_attr($options['id']); ?>-clear">
                    Clear Filters
                </button>
            </div>
            <div class="flv-stats" id="<?php echo esc_attr($options['id']); ?>-stats">
                Showing all files
            </div>
        </div>
        
        <div class="flv-table-container">
            <table class="flv-table" id="<?php echo esc_attr($options['id']); ?>-table">
                <thead>
                    <tr>
                        <th class="row-number">#</th>
                        <th class="sortable" data-sort="name">Filename</th>
                        <th class="sortable" data-sort="modified">Modified Date</th>
                        <th class="sortable" data-sort="size">File Size</th>
                    </tr>
                </thead>
                <tbody id="<?php echo esc_attr($options['id']); ?>-tbody">
                    <!-- Files will be rendered here by JavaScript -->
                </tbody>
            </table>
        </div>
        
        <?php if ($options['show_debug']): ?>
        <button class="flv-toggle-debug" id="<?php echo esc_attr($options['id']); ?>-toggle-debug">
            Toggle Debug Panel
        </button>
        <div class="flv-debug-panel" id="<?php echo esc_attr($options['id']); ?>-debug" style="display: none;">
            <h4>Debug Information</h4>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Search Query:</span>
                <span class="flv-debug-value" id="<?php echo esc_attr($options['id']); ?>-debug-search">None</span>
            </div>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Date Filter:</span>
                <span class="flv-debug-value" id="<?php echo esc_attr($options['id']); ?>-debug-date">None</span>
            </div>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Sort Column:</span>
                <span class="flv-debug-value" id="<?php echo esc_attr($options['id']); ?>-debug-sort">None</span>
            </div>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Sort Direction:</span>
                <span class="flv-debug-value" id="<?php echo esc_attr($options['id']); ?>-debug-direction">None</span>
            </div>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Total Files:</span>
                <span class="flv-debug-value" id="<?php echo esc_attr($options['id']); ?>-debug-total">0</span>
            </div>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Visible Files:</span>
                <span class="flv-debug-value" id="<?php echo esc_attr($options['id']); ?>-debug-visible">0</span>
            </div>
            <div class="flv-debug-item">
                <span class="flv-debug-label">Data Source:</span>
                <span class="flv-debug-value"><?php echo esc_html(is_string($data_source) ? $data_source : 'Custom'); ?></span>
            </div>
        </div>
        <?php endif; ?>
        
        <script>
        (function() {
            const viewerId = '<?php echo esc_js($options['id']); ?>';
            const filesData = <?php echo json_encode($files); ?>;
            
            class FileListingViewer {
                constructor(id, files) {
                    this.id = id;
                    this.originalFiles = files;
                    this.files = [...files];
                    this.filteredFiles = [...files];
                    this.sortColumn = null;
                    this.sortDirection = null;
                    this.searchQuery = '';
                    this.dateFilter = null;
                    
                    this.init();
                }
                
                init() {
                    this.bindEvents();
                    this.render();
                    this.updateDebug();
                }
                
                bindEvents() {
                    // Search input
                    document.getElementById(`${this.id}-search`).addEventListener('input', (e) => {
                        this.searchQuery = e.target.value;
                        this.applyFilters();
                    });
                    
                    // Date filter
                    document.getElementById(`${this.id}-date`).addEventListener('change', (e) => {
                        this.dateFilter = e.target.value;
                        this.applyFilters();
                    });
                    
                    // Clear filters
                    document.getElementById(`${this.id}-clear`).addEventListener('click', () => {
                        document.getElementById(`${this.id}-search`).value = '';
                        document.getElementById(`${this.id}-date`).value = '';
                        this.searchQuery = '';
                        this.dateFilter = null;
                        this.applyFilters();
                    });
                    
                    // Sort columns
                    document.querySelectorAll(`#${this.id}-table th.sortable`).forEach(th => {
                        th.addEventListener('click', () => {
                            this.sort(th.dataset.sort);
                        });
                    });
                    
                    // Debug panel toggle
                    const debugToggle = document.getElementById(`${this.id}-toggle-debug`);
                    if (debugToggle) {
                        debugToggle.addEventListener('click', () => {
                            const panel = document.getElementById(`${this.id}-debug`);
                            panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
                        });
                    }
                }
                
                fuzzyMatch(text, query) {
                    text = text.toLowerCase();
                    query = query.toLowerCase();
                    
                    let queryIndex = 0;
                    for (let i = 0; i < text.length && queryIndex < query.length; i++) {
                        if (text[i] === query[queryIndex]) {
                            queryIndex++;
                        }
                    }
                    return queryIndex === query.length;
                }
                
                applyFilters() {
                    this.filteredFiles = this.files.filter(file => {
                        // Search filter (fuzzy matching)
                        if (this.searchQuery) {
                            if (!this.fuzzyMatch(file.name, this.searchQuery)) {
                                return false;
                            }
                        }
                        
                        // Date filter
                        if (this.dateFilter) {
                            const fileDate = new Date(file.modified).toISOString().split('T')[0];
                            if (fileDate !== this.dateFilter) {
                                return false;
                            }
                        }
                        
                        return true;
                    });
                    
                    this.render();
                    this.updateStats();
                    this.updateDebug();
                }
                
                sort(column) {
                    // Toggle direction if same column
                    if (this.sortColumn === column) {
                        this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
                    } else {
                        this.sortColumn = column;
                        this.sortDirection = 'asc';
                    }
                    
                    this.files.sort((a, b) => {
                        let aVal = a[column];
                        let bVal = b[column];
                        
                        // Handle different data types
                        if (column === 'size') {
                            aVal = parseInt(aVal) || 0;
                            bVal = parseInt(bVal) || 0;
                        } else if (column === 'modified') {
                            aVal = new Date(aVal).getTime();
                            bVal = new Date(bVal).getTime();
                        } else {
                            aVal = aVal.toLowerCase();
                            bVal = bVal.toLowerCase();
                        }
                        
                        if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
                        if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
                        return 0;
                    });
                    
                    this.applyFilters();
                    this.updateSortIndicators();
                    this.updateDebug();
                }
                
                updateSortIndicators() {
                    document.querySelectorAll(`#${this.id}-table th.sortable`).forEach(th => {
                        th.classList.remove('sort-asc', 'sort-desc');
                        if (th.dataset.sort === this.sortColumn) {
                            th.classList.add(`sort-${this.sortDirection}`);
                        }
                    });
                }
                
                formatFileSize(bytes) {
                    if (!bytes || bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }
                
                formatDate(dateString) {
                    const date = new Date(dateString);
                    return date.toLocaleString();
                }
                
                highlightText(text, query) {
                    if (!query) return text;
                    
                    const regex = new RegExp(`(${query.split('').join('.*?')})`, 'gi');
                    return text.replace(regex, '<span class="flv-highlight">$1</span>');
                }
                
                render() {
                    const tbody = document.getElementById(`${this.id}-tbody`);
                    
                    if (this.filteredFiles.length === 0) {
                        tbody.innerHTML = `
                            <tr>
                                <td colspan="4" class="flv-no-results">
                                    No files found matching your criteria
                                </td>
                            </tr>
                        `;
                        return;
                    }
                    
                    tbody.innerHTML = this.filteredFiles.map((file, index) => {
                        const highlightedName = this.highlightText(file.name, this.searchQuery);
                        return `
                            <tr>
                                <td class="row-number">${index + 1}</td>
                                <td class="file-name">${highlightedName}</td>
                                <td>${this.formatDate(file.modified)}</td>
                                <td class="file-size">${this.formatFileSize(file.size)}</td>
                            </tr>
                        `;
                    }).join('');
                }
                
                updateStats() {
                    const stats = document.getElementById(`${this.id}-stats`);
                    const total = this.files.length;
                    const visible = this.filteredFiles.length;
                    
                    if (this.searchQuery || this.dateFilter) {
                        stats.textContent = `Showing ${visible} of ${total} files (filtered)`;
                    } else {
                        stats.textContent = `Showing all ${total} files`;
                    }
                }
                
                updateDebug() {
                    const debugElements = {
                        search: document.getElementById(`${this.id}-debug-search`),
                        date: document.getElementById(`${this.id}-debug-date`),
                        sort: document.getElementById(`${this.id}-debug-sort`),
                        direction: document.getElementById(`${this.id}-debug-direction`),
                        total: document.getElementById(`${this.id}-debug-total`),
                        visible: document.getElementById(`${this.id}-debug-visible`)
                    };
                    
                    if (debugElements.search) {
                        debugElements.search.textContent = this.searchQuery || 'None';
                        debugElements.date.textContent = this.dateFilter || 'None';
                        debugElements.sort.textContent = this.sortColumn || 'None';
                        debugElements.direction.textContent = this.sortDirection || 'None';
                        debugElements.total.textContent = this.files.length;
                        debugElements.visible.textContent = this.filteredFiles.length;
                    }
                }
            }
            
            // Initialize the viewer
            new FileListingViewer(viewerId, filesData);
        })();
        </script>
    </div>
    <?php
}

/**
 * Get file data from various sources
 */
function get_file_data($source, $options) {
    // If source is an array, return it directly
    if (is_array($source)) {
        return $source;
    }
    
    // If source is a callable, call it
    if (is_callable($source)) {
        return call_user_func($source, $options);
    }
    
    // If source is a directory path, scan it
    if (is_string($source) && is_dir($source)) {
        return scan_directory($source, $options);
    }
    
    // Demo data
    if ($source === 'demo') {
        return [
            ['name' => 'document.pdf', 'size' => 2048576, 'modified' => '2024-01-15T10:30:00'],
            ['name' => 'image.jpg', 'size' => 512000, 'modified' => '2024-01-14T14:20:00'],
            ['name' => 'spreadsheet.xlsx', 'size' => 1024000, 'modified' => '2024-01-13T09:15:00'],
            ['name' => 'presentation.pptx', 'size' => 3072000, 'modified' => '2024-01-12T16:45:00'],
            ['name' => 'archive.zip', 'size' => 5120000, 'modified' => '2024-01-11T11:00:00'],
            ['name' => 'video.mp4', 'size' => 10485760, 'modified' => '2024-01-10T13:30:00'],
            ['name' => 'readme.txt', 'size' => 2048, 'modified' => '2024-01-09T08:00:00'],
            ['name' => 'config.json', 'size' => 4096, 'modified' => '2024-01-08T17:20:00'],
            ['name' => 'database.sql', 'size' => 8192000, 'modified' => '2024-01-07T12:10:00'],
            ['name' => 'backup_2024.tar.gz', 'size' => 15728640, 'modified' => '2024-01-06T22:00:00'],
        ];
    }
    
    return [];
}

/**
 * Scan a directory and return file information
 */
function scan_directory($path, $options) {
    $files = [];
    
    if (!is_dir($path)) {
        return $files;
    }
    
    $iterator = new DirectoryIterator($path);
    foreach ($iterator as $fileInfo) {
        if ($fileInfo->isDot() || $fileInfo->isDir()) {
            continue;
        }
        
        $files[] = [
            'name' => $fileInfo->getFilename(),
            'size' => $fileInfo->getSize(),
            'modified' => date('c', $fileInfo->getMTime())
        ];
    }
    
    return $files;
}

// Example usage for standalone mode or testing
if (defined('STANDALONE_MODE')) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>File Listing Viewer Demo</title>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body {
                margin: 0;
                padding: 20px;
                background: #f5f5f5;
            }
            .container {
                max-width: 1200px;
                margin: 0 auto;
            }
            h1 {
                color: #333;
                margin-bottom: 30px;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <h1>File Listing Viewer Demo</h1>
            
            <?php
            // Example 1: Using demo data
            render_file_listing_viewer('demo', [
                'show_debug' => true
            ]);
            
            // Example 2: Using custom data
            /*
            $custom_files = [
                ['name' => 'custom1.txt', 'size' => 1024, 'modified' => '2024-01-20T10:00:00'],
                ['name' => 'custom2.doc', 'size' => 2048, 'modified' => '2024-01-19T11:00:00'],
            ];
            render_file_listing_viewer($custom_files, [
                'id' => 'custom-viewer',
                'show_debug' => false
            ]);
            */
            
            // Example 3: Using a directory scan
            // render_file_listing_viewer('/path/to/directory');
            
            // Example 4: Using a callback function
            /*
            function get_custom_files($options) {
                // Your custom logic here
                return [
                    ['name' => 'dynamic1.pdf', 'size' => 3072, 'modified' => '2024-01-18T12:00:00'],
                    ['name' => 'dynamic2.zip', 'size' => 4096, 'modified' => '2024-01-17T13:00:00'],
                ];
            }
            render_file_listing_viewer('get_custom_files');
            */
            ?>
        </div>
    </body>
    </html>
    <?php
}
?>