# KISS Automated PDF Linker - Architecture Documentation

## Overview

Version 3.0.0 represents a complete architectural overhaul of the KISS Automated PDF Linker plugin, transitioning from a monolithic procedural codebase to a modern, object-oriented, PSR-4 compliant structure.

## Architecture Principles

### 1. **Separation of Concerns**
- **Core**: Plugin lifecycle management
- **Admin**: Backend administration functionality
- **Frontend**: User-facing features
- **Services**: Business logic and data processing
- **Utils**: Shared utilities and helpers

### 2. **Dependency Injection**
- Services are injected into classes that need them
- Promotes testability and loose coupling
- Centralized service management through the main Plugin class

### 3. **PSR-4 Autoloading**
- Namespace: `KissPlugins\AutomatedPdfLinker`
- Directory structure mirrors namespace hierarchy
- Automatic class loading without manual includes

## Directory Structure

```
src/
├── Core/
│   ├── Plugin.php          # Main plugin orchestrator
│   ├── Activator.php       # Plugin activation logic
│   └── Deactivator.php     # Plugin deactivation logic
├── Admin/
│   ├── Settings.php        # Settings API management
│   ├── AdminMenu.php       # Admin menu and pages
│   └── Assets.php          # Admin asset enqueuing
├── Frontend/
│   ├── Shortcode.php       # Shortcode handler and WooCommerce integration
│   └── Assets.php          # Frontend asset enqueuing
├── Services/
│   ├── IndexBuilder.php    # PDF index building and management
│   ├── FileScanner.php     # Directory scanning logic
│   ├── FuzzyMatcher.php    # Fuzzy matching algorithms
│   └── CacheManager.php    # Index caching and storage
└── Utils/
    ├── FileNormalizer.php  # Filename normalization utilities
    └── Logger.php          # Debug logging functionality
```

## Key Classes

### Core\Plugin
**Responsibility**: Main plugin orchestrator and service container
- Singleton pattern for global access
- Manages service instantiation and dependency injection
- Coordinates WordPress hook registration
- Provides access to all plugin services
- Integrates with advanced DI container system

### Core\Container
**Responsibility**: Advanced dependency injection container
- Automatic dependency resolution via reflection
- Service binding with singleton and instance support
- Service provider system for organized registration
- Parameter injection and method calling with DI
- Alias support for flexible service naming
- Container clearing for testing environments

### Services\IndexBuilder
**Responsibility**: PDF index building and management
- Orchestrates the scanning and indexing process
- Validates index integrity
- Provides index statistics and status information
- Handles error reporting during index building

### Services\FileScanner
**Responsibility**: Directory scanning and file discovery
- Recursively scans selected directories for PDF files
- Handles file system errors gracefully
- Provides available directory enumeration
- Creates normalized file information structures

### Services\FuzzyMatcher
**Responsibility**: Fuzzy matching algorithms
- Implements similarity-based file matching
- Supports both shortcode and product-based matching
- Configurable similarity thresholds
- Detailed match logging for debugging

### Services\CacheManager
**Responsibility**: Index storage and retrieval
- Multiple storage strategies (database, file, compressed)
- Automatic fallback mechanisms
- Performance optimization for large indexes
- Storage integrity validation

## Data Flow

### Index Building Process
1. **Settings** → Provides selected directories
2. **FileScanner** → Scans directories for PDF files
3. **FileNormalizer** → Normalizes filenames for matching
4. **CacheManager** → Stores index with appropriate strategy
5. **IndexBuilder** → Orchestrates process and handles errors

### Shortcode Processing
1. **Shortcode** → Receives shortcode attributes
2. **CacheManager** → Retrieves PDF index
3. **FuzzyMatcher** → Finds best matching file
4. **Shortcode** → Generates HTML output

### WooCommerce Integration
1. **Shortcode** → Hooks into WooCommerce product tabs
2. **FuzzyMatcher** → Matches product title to PDF files
3. **Shortcode** → Renders strain content with PDF links

## Configuration

### Settings Structure
```php
[
    'selected_directories'     => [], // Array of directory names to scan
    'link_color'              => '#0000FF', // Hex color for PDF links
    'use_product_title_match' => false, // Enable WooCommerce integration
    'debug_logging'           => false, // Enable debug logging
]
```

### Constants (Backward Compatibility)
- `KAPL_VERSION`: Plugin version
- `KAPL_PLUGIN_DIR`: Plugin directory path
- `KAPL_PLUGIN_URL`: Plugin URL
- `KAPL_SETTINGS_OPTION_NAME`: Settings option name
- `KAPL_INDEX_OPTION_NAME`: Index option name
- `KAPL_SIMILARITY_THRESHOLD`: Fuzzy matching threshold
- `KAPL_SHORTCODE_TAG`: Shortcode tag name
- `KAPL_SETTINGS_SLUG`: Settings page slug

## Error Handling

### Graceful Degradation
- Missing index → User-friendly error messages
- File system errors → Logged with fallback behavior
- Invalid settings → Sanitized with sensible defaults
- Storage failures → Automatic fallback to alternative storage

### Logging
- Configurable debug logging through settings
- Structured log messages with timestamps and levels
- Error context preservation for debugging
- Performance-conscious logging (only when enabled)

## Performance Considerations

### Index Storage
- Automatic compression for large indexes
- File-based fallback for database limitations
- Non-autoloaded options to prevent performance impact
- Efficient JSON encoding/decoding

### Caching Strategy
- Index cached until explicitly rebuilt
- Lazy loading of services
- Minimal WordPress hook registration
- Optimized asset loading (admin/frontend separation)

## Testing Strategy

### Unit Tests
- Individual class and method testing
- Mock dependencies for isolation
- Edge case and error condition coverage
- Performance benchmarking for critical paths

### Integration Tests
- WordPress environment integration
- Database interaction testing
- File system operation validation
- End-to-end workflow testing

## Migration from v2.x

### Backward Compatibility
- All existing shortcodes continue to work
- Settings are automatically migrated
- Existing indexes are compatible
- No user action required for upgrade

### Breaking Changes
- Internal function names changed (not public API)
- File structure completely reorganized
- Class-based architecture replaces procedural code
- PSR-4 autoloading replaces manual includes

## Development Guidelines

### Adding New Features
1. Identify appropriate namespace and directory
2. Follow PSR-4 naming conventions
3. Inject dependencies through constructor
4. Add comprehensive unit tests
5. Update documentation

### Remote Git Updater Integration

The plugin includes **Yahnis Elsts' Plugin Update Checker v5** for automatic updates from GitHub:

- **Library Location**: `lib/plugin-update-checker/`
- **Integration**: Main plugin file (`kiss-automated-pdf-linker-v3.php`)
- **Repository**: `https://github.com/kissplugins/automated-pdf-linker`
- **Branch**: `main`
- **Features**:
  - Automatic update checks from GitHub releases
  - WordPress admin integration (shows in Plugins page)
  - One-click updates directly from WordPress
  - Version comparison and error handling
  - Professional deployment workflow

This enables **seamless automatic updates** for end users without requiring manual plugin downloads.

### Code Standards
- PSR-12 coding standards
- WordPress coding standards for WordPress-specific code
- Comprehensive PHPDoc comments
- Type hints for all parameters and return values
- Meaningful variable and method names

---

*Last updated: 2025-08-30*
*Version: 3.0.0*
