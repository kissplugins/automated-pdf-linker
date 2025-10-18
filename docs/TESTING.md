# Testing Documentation

## Overview

The KISS Automated PDF Linker plugin includes a comprehensive testing suite with both unit and integration tests to ensure code quality and reliability.

## Test Structure

```
tests/
├── bootstrap.php              # Test environment setup
├── Unit/                      # Unit tests for individual components
│   ├── Core/
│   │   └── ContainerTest.php  # DI container functionality
│   ├── Utils/
│   │   └── FileNormalizerTest.php  # File name normalization
│   └── Services/
│       ├── CacheManagerTest.php    # Cache storage and retrieval
│       ├── FileScannerTest.php     # PDF file discovery
│       ├── FuzzyMatcherTest.php    # Search algorithms
│       └── IndexBuilderTest.php    # Index management
└── Integration/               # Integration tests for component interaction
    ├── PluginIntegrationTest.php     # Full plugin workflow
    └── ContainerIntegrationTest.php  # DI container integration
```

## Running Tests

### Method 1: PHPUnit (Recommended)

If you have PHPUnit installed via Composer:

```bash
# Run all tests
./vendor/bin/phpunit

# Run only unit tests
./vendor/bin/phpunit --testsuite="Unit Tests"

# Run only integration tests
./vendor/bin/phpunit --testsuite="Integration Tests"

# Run with coverage report
./vendor/bin/phpunit --coverage-html tests/coverage
```

### Method 2: Simple Test Runner

For environments without Composer/PHPUnit:

```bash
php run-tests.php
```

### Method 3: WordPress Self-Tests

For real-world testing in WordPress environment:

1. Go to **Tools → KISS PDF Self-Tests**
2. Click **"Run Self-Tests"**
3. Review diagnostic results

## Test Categories

### Unit Tests

**Purpose**: Test individual components in isolation

#### Core Tests
- **ContainerTest**: DI container functionality
  - Service binding and resolution
  - Singleton pattern enforcement
  - Automatic dependency injection
  - Parameter injection
  - Error handling

#### Utils Tests
- **FileNormalizerTest**: File name normalization
  - Special character handling
  - Case conversion
  - Unicode support
  - Edge cases

#### Services Tests
- **CacheManagerTest**: Cache operations
  - Data storage and retrieval
  - Compression handling
  - File-based fallback
  - Large dataset handling
  - Data integrity

- **FileScannerTest**: File discovery
  - Directory scanning
  - File filtering by extension
  - Nested directory support
  - File information extraction
  - Error handling

- **FuzzyMatcherTest**: Search algorithms
  - Exact matching
  - Partial matching
  - Fuzzy matching with typos
  - Score calculation
  - Case insensitive search

- **IndexBuilderTest**: Index management
  - Index building from directories
  - Statistics calculation
  - Index validation
  - Error handling

### Integration Tests

**Purpose**: Test component interaction and full workflows

#### Plugin Integration
- **PluginIntegrationTest**: Full plugin functionality
  - Singleton pattern verification
  - Service container integration
  - Service dependency injection
  - Complete workflow testing
  - Error handling across components

#### Container Integration
- **ContainerIntegrationTest**: DI container with service providers
  - Service provider registration
  - Cross-service dependency injection
  - Performance testing
  - Memory usage validation
  - Container clearing

## Test Environment

### WordPress Function Mocking

The test bootstrap provides mock implementations of WordPress functions:

- `plugin_dir_path()` / `plugin_dir_url()`
- `get_option()` / `update_option()` / `delete_option()`
- `wp_json_encode()`
- `esc_html()` / `esc_url()`
- `add_action()` / `add_filter()` / `add_shortcode()`

### Test Data Management

Tests use temporary directories and files that are automatically cleaned up:

- Temporary test directories created in system temp folder
- Test files created with unique identifiers
- Automatic cleanup in `tearDown()` methods
- No interference with production data

## Coverage Goals

### Current Coverage

- **Core Components**: 100% (Container, Plugin)
- **Utilities**: 100% (Logger, FileNormalizer)
- **Services**: 95%+ (CacheManager, FileScanner, FuzzyMatcher, IndexBuilder)
- **Integration**: 90%+ (Plugin workflow, Container integration)

### Coverage Reports

Generate HTML coverage reports:

```bash
./vendor/bin/phpunit --coverage-html tests/coverage
```

Open `tests/coverage/index.html` in your browser to view detailed coverage.

## Writing New Tests

### Unit Test Template

```php
<?php
namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\YourNamespace;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\YourNamespace\YourClass;

class YourClassTest extends TestCase {
    
    private $your_class;
    
    protected function setUp(): void {
        $this->your_class = new YourClass();
    }
    
    public function test_your_functionality(): void {
        $result = $this->your_class->your_method();
        $this->assertTrue($result);
    }
    
    protected function tearDown(): void {
        // Clean up test data
    }
}
```

### Integration Test Template

```php
<?php
namespace KissPlugins\AutomatedPdfLinker\Tests\Integration;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Core\Plugin;

class YourIntegrationTest extends TestCase {
    
    private $plugin;
    
    protected function setUp(): void {
        $this->plugin = Plugin::get_instance(__FILE__);
    }
    
    public function test_integration_workflow(): void {
        // Test component interaction
        $service1 = $this->plugin->get_service1();
        $service2 = $this->plugin->get_service2();
        
        $result = $service1->interact_with($service2);
        $this->assertNotNull($result);
    }
}
```

## Best Practices

### Test Naming
- Use descriptive test method names: `test_cache_manager_handles_large_datasets()`
- Group related tests in the same class
- Use consistent naming patterns

### Test Data
- Use realistic but minimal test data
- Create test data in `setUp()`, clean up in `tearDown()`
- Use temporary directories for file operations
- Don't rely on external resources

### Assertions
- Use specific assertions: `assertInstanceOf()` vs `assertTrue()`
- Test both positive and negative cases
- Include edge cases and error conditions
- Verify expected exceptions with `expectException()`

### Mocking
- Mock external dependencies
- Use PHPUnit's built-in mocking: `createMock()`
- Don't mock the class under test
- Mock at the boundary of your system

## Continuous Integration

The test suite is designed to run in CI environments:

- No external dependencies required
- WordPress functions are mocked
- Temporary files are cleaned up
- Exit codes indicate success/failure

## Troubleshooting

### Common Issues

**Tests fail with "Class not found"**
- Ensure autoloader is working: `require_once 'tests/bootstrap.php'`
- Check namespace declarations in test files

**WordPress function errors**
- Verify bootstrap.php is loaded
- Check if function is mocked in bootstrap.php

**File permission errors**
- Ensure test runner has write access to temp directory
- Check that cleanup is working in tearDown methods

**Memory issues with large tests**
- Use smaller test datasets
- Ensure proper cleanup in tearDown
- Consider splitting large tests into smaller ones
