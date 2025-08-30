# KISS Automated PDF Linker - Self-Test System

## Overview

Version 3.0.0 introduces a comprehensive on-screen self-test system designed to help with regression prevention and system diagnostics. The self-tests are accessible directly from the WordPress admin interface and provide immediate feedback on plugin functionality.

## Accessing Self-Tests

1. Navigate to **Settings → KISS PDF Linker** in your WordPress admin
2. Scroll down to the **System Self-Tests** section
3. Click **"Run Self-Tests"** to execute all diagnostic tests
4. Review the results for any issues or failures

## Test Categories

### 🔧 **Core Functionality Tests**

#### Plugin Initialization
- ✅ Verifies plugin singleton pattern is working
- ✅ Confirms correct version number (3.0.0)
- ✅ Validates plugin instance creation

#### PSR-4 Autoloader
- ✅ Tests automatic class loading
- ✅ Verifies namespace resolution
- ✅ Confirms all core classes are loadable

#### Settings Access
- ✅ Validates settings structure
- ✅ Confirms all required configuration keys exist
- ✅ Tests settings retrieval and defaults

### ⚙️ **Service Layer Tests**

#### Cache Manager
- ✅ Tests index storage and retrieval
- ✅ Validates data integrity
- ✅ Confirms fallback storage mechanisms
- ✅ Tests cache clearing functionality

#### File Normalizer
- ✅ Tests filename normalization algorithms
- ✅ Validates camelCase splitting
- ✅ Confirms special character handling
- ✅ Tests edge cases (empty strings, complex filenames)

#### Fuzzy Matcher
- ✅ Tests similarity matching algorithms
- ✅ Validates threshold-based matching
- ✅ Confirms best match selection
- ✅ Tests product title matching

#### Logger
- ✅ Validates logger method availability
- ✅ Tests logging level support
- ✅ Confirms debug/info/warn/error methods

### 🔌 **WordPress Integration Tests**

#### Shortcode Registration
- ✅ Confirms `[kiss_pdf]` shortcode is registered
- ✅ Validates shortcode handler assignment
- ✅ Tests WordPress shortcode system integration

#### Admin Menu
- ✅ Verifies admin menu hooks are registered
- ✅ Confirms settings page accessibility
- ✅ Tests WordPress admin integration

#### Uploads Directory Access
- ✅ Validates WordPress uploads directory exists
- ✅ Confirms directory read permissions
- ✅ Tests file system accessibility

### 📊 **Index System Tests**

#### Index Functionality
- ✅ Tests index building capabilities
- ✅ Validates index structure and integrity
- ✅ Confirms index statistics accuracy
- ✅ Tests index validation algorithms

## Test Results Interpretation

### ✅ **Green (Passed)**
- Test completed successfully
- No issues detected
- Functionality is working as expected

### ❌ **Red (Failed)**
- Test encountered an error or unexpected result
- Requires investigation and potential fixes
- May indicate regression or configuration issues

### 📊 **Summary Statistics**
- **Total Tests**: Number of tests executed
- **Passed**: Number of successful tests
- **Failed**: Number of failed tests
- **Success Rate**: Percentage of tests that passed

## Common Test Scenarios

### 🆕 **Fresh Installation**
```
✅ Plugin Initialization - Plugin initialized successfully
✅ PSR-4 Autoloader - All test classes loaded successfully
✅ Settings Access - All required settings keys present
✅ Cache Manager - Cache manager store/retrieve working correctly
✅ File Normalizer - All normalization test cases passed
✅ Fuzzy Matcher - Fuzzy matching working correctly
✅ Logger - All logger methods available
✅ Shortcode Registration - Shortcode [kiss_pdf] is registered
✅ Admin Menu - Admin menu hooks are registered
✅ Uploads Directory Access - Uploads directory is accessible
✅ Index Functionality - No index found but system is functional

Summary: 11/11 tests passed (100% success rate)
```

### 🔄 **After Index Build**
```
✅ Index Functionality - Index is ready with 25 files and passes validation

Summary: 11/11 tests passed (100% success rate)
```

### ⚠️ **With Issues**
```
❌ Uploads Directory Access - Uploads directory is not readable
❌ Index Functionality - Index validation failed: Invalid index item

Summary: 9/11 tests passed (81.8% success rate)
```

## Troubleshooting Failed Tests

### **Plugin Initialization Failures**
- Check for PHP version compatibility (requires 7.4+)
- Verify WordPress version (requires 5.2+)
- Ensure no plugin conflicts

### **Autoloader Failures**
- Verify `vendor/autoload.php` exists
- Check file permissions
- Confirm PSR-4 namespace structure

### **Settings Access Failures**
- Check database connectivity
- Verify WordPress options table integrity
- Confirm user permissions

### **Cache Manager Failures**
- Check database write permissions
- Verify file system write access
- Confirm WordPress options functionality

### **Uploads Directory Failures**
- Check file system permissions
- Verify WordPress uploads configuration
- Confirm directory exists and is readable

### **Index Functionality Failures**
- Rebuild the PDF index
- Check selected directories configuration
- Verify file permissions in scan directories

## Regression Prevention

### **When to Run Self-Tests**

1. **After Plugin Updates**
   - Verify all functionality still works
   - Catch any breaking changes
   - Confirm compatibility

2. **After WordPress Updates**
   - Test WordPress integration
   - Verify API compatibility
   - Check for deprecated function usage

3. **After Server Changes**
   - Validate file system access
   - Confirm PHP version compatibility
   - Test database connectivity

4. **Before Production Deployment**
   - Comprehensive functionality check
   - Catch configuration issues
   - Verify environment compatibility

5. **Regular Maintenance**
   - Monthly health checks
   - Proactive issue detection
   - Performance validation

### **Automated Testing Integration**

The self-test system can be integrated with automated testing workflows:

```php
// Example: Programmatic test execution
$plugin = \KissPlugins\AutomatedPdfLinker\Core\Plugin::get_instance();
$self_test = new \KissPlugins\AutomatedPdfLinker\Admin\SelfTest( $plugin );
$results = $self_test->run_all_tests();
$summary = $self_test->get_test_summary();

if ( $summary['failed'] > 0 ) {
    // Handle failures
    error_log( "KAPL Self-Tests Failed: {$summary['failed']} failures detected" );
}
```

## Benefits

### **For Developers**
- **Immediate Feedback**: Quickly identify broken functionality
- **Regression Detection**: Catch issues before they reach production
- **Debugging Aid**: Pinpoint specific component failures
- **Confidence**: Deploy with assurance that core functionality works

### **For Site Administrators**
- **Health Monitoring**: Regular system health checks
- **Issue Prevention**: Catch problems before users notice
- **Troubleshooting**: Clear diagnostic information for support
- **Maintenance**: Proactive system maintenance

### **For Support**
- **Diagnostic Tool**: Quick system status overview
- **Issue Isolation**: Identify specific problem areas
- **Verification**: Confirm fixes are working
- **Documentation**: Clear test results for support tickets

---

*Last updated: 2025-08-30*
*Version: 3.0.0*
