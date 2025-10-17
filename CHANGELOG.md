# Changelog

All notable changes to the KISS Automated PDF Linker plugin will be documented in this file.




## [3.1.6] - 2025-10-17

### Added
- Phase 1 Folder Viewer UI: client-side search, date filter, and sort toggles.
  - Single flexible search input filters filename/path/folder (debounced)
  - Partial/fuzzy filename matching (lightweight subsequence/contains)
  - Date filter accepts `YYYY-MM-DD` and `MM-DD-YY`/`MM/DD/YY` and matches by Modified Date
  - Sort by Date and Sort by Name toggles with visual state
  - Live “Showing X of Y” counter updates as filters change

### Notes
- DRY preserved: totals continue to use IndexBuilder::get_index_stats().
- Admin page hook detection remains slug-based to support both Tools and Settings screens.

## [3.1.4] - 2025-10-17
## [3.1.5] - 2025-10-17

### Planning
- Added PROJECT-FILELIST-V2.md with a 2-phase plan for search/filter/sort in the Folder Viewer.
- No functional changes in this version; planning-only.



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

