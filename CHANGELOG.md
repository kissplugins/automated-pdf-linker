# Changelog

All notable changes to the KISS Automated PDF Linker plugin will be documented in this file.



## [3.1.8] - 2025-10-18
## [3.1.9] - 2025-10-18
## [3.1.10] - 2025-10-18
## [3.1.11] - 2025-10-18

## [3.1.12] - 2025-10-18

### Fixed
- Self-Test: Cache Manager test is now non-destructive and performs a safe roundtrip check instead of overwriting the index with a single-item fixture.
- Self-Test: Viewer Empty State assertion relaxed to only require the friendly "No files found matching your criteria" message (no longer fails on presence/absence of transient "Initializing/Loading" markers).


### Added
- Three new Self-Tests to prevent regressions in the File Listing page and viewer: toolbar rebuild button/nonce/spinner presence, clickable filename anchors, and empty-state rendering.



### Added
- “Rebuild PDF Index Now” button to the top-right of the File Listing page, including nonce and server-side handling. Success/error notices are shown inline.
- Shows a progress spinner on the File Listing page while a rebuild request is being processed.




### Changed
- Simplified the System Self-Tests section copy and button on the Settings page.



### Added
- Filenames in the File Listing are now clickable and open the PDF in a new tab.

### Changed
- Tightened search: uses tokenized, case-insensitive substring matching (all query words must appear), reducing overly fuzzy matches like "air" that previously matched many unrelated files.


## [3.1.7] - 2025-10-18

### Fixed
- File Listing page could get stuck on “Loading…” if admin JS errors elsewhere prevented our inline script from running. The viewer now server-renders initial rows so data is visible even before JS executes.

### Improved
- Added top-level try/catch around viewer boot to surface an inline error row if initialization fails.
- Added an early "Initializing…" marker so it’s obvious when our script starts running.


## [3.1.6] - 2025-10-18

### Fixed
- File Listing page: rows not rendering in some environments due to client-side errors when data was null or DOM controls were unavailable. The viewer now guards against null/undefined and missing elements and always renders a friendly "No files found" row if empty.

### Changed
- Replaced raw viewer include with a WP‑friendly, namespaced component `Admin\Components\FileListingViewer` and updated the Tools page to call it directly.
- Added an informational notice on the File Listing page when the index is empty, with a quick link to the Settings page to rebuild the index.


## [3.1.5] - 2025-10-17

### Added
- New Tools page: "KISS PDF Linker File Listing" — a flat, searchable and sortable file list using the universal viewer component. Includes fuzzy name search, date filter, and sortable columns.

### Notes
- DRY: Reuses IndexBuilder data to populate the viewer. No duplicate scanning logic.
- The viewer is self-styled and does not rely on the settings screen assets.

## [3.1.4] - 2025-10-17

### Improved
- High-contrast styling for the “Total files” summary in the Folder Viewer to improve legibility on dark UI.

### Changed
- DRY: The Folder Viewer now re-uses IndexBuilder::get_index_stats() to display the same total file count shown near the Rebuild Index status.
- Admin stylesheet version bumped to 3.0.3 to refresh CSS in browsers.

## [3.1.3] - 2025-10-17

### Added
- Folder Viewer now shows a total file count at the top and numbers each row starting from `1.)` across all folders.

### Notes
- Added prominent code comments explaining why we detect the admin page by slug (`strpos($hook, Settings::SETTINGS_SLUG)`) to support both `tools_page_` and `settings_page_` contexts. This prevents accidental regressions.

## [3.1.2] - 2025-10-17

### Fixed
- Debug assets were not loading on the Tools screen due to using the wrong admin page hook prefix. Now we detect our screen by slug and load for both `tools_page_` and `settings_page_` contexts.

### Changed
- Inline debug JS now attaches to the existing `kapl-admin-script` handle to guarantee execution.
- Added persistent “Enable on-screen debug panel” setting under Debugging; URL `kapl_debug=1` still overrides.

## [3.1.1] - 2025-10-17

### Added
- On-screen debugging for the Folder File Listing Viewer (toggle via `?kapl_debug=1`)
- Debug panel shows whether `assets/admin.css` is enqueued, its src, version, and filemtime
- Live measurements of table layout mode and first-row column widths/paddings
- Visual highlights for the three columns to inspect spacing quickly

### Changed
- Bumped admin stylesheet handle version to `3.0.2` to force cache refresh
- Plugin version bumped to `3.1.1`


## [3.1.0] - 2025-10-17

### Added
- **File Metadata in Index**: Modified date and file size are now collected during PDF indexing
- **Folder & Viewer Enhancements**: Display of file modification dates and file sizes in the Folder File Listing Viewer
- **Index Migration**: Automatic detection and migration of old indexes to include new metadata fields
- **Index Versioning**: Version tracking for the PDF index to support future migrations

### Changed
- Enhanced `FileScanner.create_file_info()` to include `modified` (timestamp) and `size_bytes` fields
- Updated `CacheManager` to track index version for migration support
- Settings page now automatically triggers index migration when needed

### Technical Details
- Index version constant: `3.1.0`
- New cache manager constants: `INDEX_VERSION_OPTION_NAME`, `CURRENT_INDEX_VERSION`
- New `IndexBuilder` method: `check_and_migrate_index()` for automatic migration
- New `Settings` method: `check_and_perform_migration()` for migration handling

### Migration Notes
- Existing indexes will be automatically migrated when the settings page is accessed
- Users will see a notification when the index is updated with new metadata
- No manual action required - the migration is automatic and transparent

## [3.0.0] - 2025-08-30

### Added
- Complete architectural overhaul to PSR-4 compliant structure
- Dependency injection container system
- Comprehensive service layer architecture
- Admin settings page with directory selection
- Folder File Listing Viewer for reviewing indexed PDFs
- Debug logging system
- Self-test system for plugin diagnostics
- Backward compatibility with v2.x settings and indexes

### Changed
- Migrated from procedural to object-oriented architecture
- Reorganized codebase into logical namespaces
- Improved error handling and logging

### Fixed
- Various stability and performance improvements

---

*For more information, visit [KISS Plugins](https://KISSplugins.com)*

