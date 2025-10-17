# Changelog

All notable changes to the KISS Automated PDF Linker plugin will be documented in this file.

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

