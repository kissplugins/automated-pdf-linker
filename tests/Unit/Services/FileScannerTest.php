<?php
/**
 * File Scanner Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Services\FileScanner;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * File Scanner Test Class
 *
 * @since 3.0.0
 */
class FileScannerTest extends TestCase {

    /**
     * File scanner instance.
     *
     * @var FileScanner
     */
    private $file_scanner;

    /**
     * Logger mock.
     *
     * @var Logger
     */
    private $logger_mock;

    /**
     * Test directory path.
     *
     * @var string
     */
    private $test_dir;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->logger_mock = $this->createMock( Logger::class );
        $this->file_scanner = new FileScanner( $this->logger_mock );
        
        // Create temporary test directory
        $this->test_dir = sys_get_temp_dir() . '/kapl_test_' . uniqid();
        mkdir( $this->test_dir, 0755, true );
    }

    /**
     * Test file scanner initialization.
     *
     * @return void
     */
    public function test_file_scanner_initialization(): void {
        $this->assertInstanceOf( FileScanner::class, $this->file_scanner );
    }

    /**
     * Test scanning empty directory.
     *
     * @return void
     */
    public function test_scan_empty_directory(): void {
        $results = $this->file_scanner->scan_directory( $this->test_dir );
        
        $this->assertIsArray( $results );
        $this->assertEmpty( $results, 'Empty directory should return empty array' );
    }

    /**
     * Test scanning directory with PDF files.
     *
     * @return void
     */
    public function test_scan_directory_with_pdf_files(): void {
        // Create test PDF files (empty files for testing)
        $test_files = [
            'document1.pdf',
            'report-2023.pdf',
            'user_guide.pdf'
        ];

        foreach ( $test_files as $filename ) {
            touch( $this->test_dir . '/' . $filename );
        }

        $results = $this->file_scanner->scan_directory( $this->test_dir );
        
        $this->assertIsArray( $results );
        $this->assertCount( 3, $results, 'Should find 3 PDF files' );
        
        // Check that all files are found
        $found_files = array_column( $results, 'filename' );
        foreach ( $test_files as $expected_file ) {
            $this->assertContains( $expected_file, $found_files );
        }
    }

    /**
     * Test scanning directory with mixed file types.
     *
     * @return void
     */
    public function test_scan_directory_mixed_file_types(): void {
        // Create mixed file types
        $files = [
            'document.pdf',    // Should be included
            'image.jpg',       // Should be excluded
            'text.txt',        // Should be excluded
            'report.pdf',      // Should be included
            'data.csv'         // Should be excluded
        ];

        foreach ( $files as $filename ) {
            touch( $this->test_dir . '/' . $filename );
        }

        $results = $this->file_scanner->scan_directory( $this->test_dir );
        
        $this->assertCount( 2, $results, 'Should find only 2 PDF files' );
        
        $found_files = array_column( $results, 'filename' );
        $this->assertContains( 'document.pdf', $found_files );
        $this->assertContains( 'report.pdf', $found_files );
        $this->assertNotContains( 'image.jpg', $found_files );
        $this->assertNotContains( 'text.txt', $found_files );
    }

    /**
     * Test scanning nested directories.
     *
     * @return void
     */
    public function test_scan_nested_directories(): void {
        // Create nested directory structure
        $nested_dir = $this->test_dir . '/subfolder';
        mkdir( $nested_dir, 0755, true );

        // Create files in both directories
        touch( $this->test_dir . '/root_file.pdf' );
        touch( $nested_dir . '/nested_file.pdf' );

        $results = $this->file_scanner->scan_directory( $this->test_dir );
        
        $this->assertCount( 2, $results, 'Should find files in nested directories' );
        
        $found_files = array_column( $results, 'filename' );
        $this->assertContains( 'root_file.pdf', $found_files );
        $this->assertContains( 'nested_file.pdf', $found_files );
    }

    /**
     * Test file information extraction.
     *
     * @return void
     */
    public function test_file_information_extraction(): void {
        $test_file = $this->test_dir . '/test_document.pdf';

        // Create a test file with some content
        file_put_contents( $test_file, 'Test PDF content for size testing' );

        $results = $this->file_scanner->scan_directory( $this->test_dir );

        $this->assertCount( 1, $results );

        $file_info = $results[0];
        $this->assertArrayHasKey( 'filename', $file_info );
        $this->assertArrayHasKey( 'path', $file_info );
        $this->assertArrayHasKey( 'size_bytes', $file_info );
        $this->assertArrayHasKey( 'modified', $file_info );
        $this->assertArrayHasKey( 'normalized_name', $file_info );

        $this->assertEquals( 'test_document.pdf', $file_info['filename'] );
        $this->assertStringContains( 'test_document.pdf', $file_info['path'] );
        $this->assertGreaterThan( 0, $file_info['size_bytes'] );
        $this->assertGreaterThan( 0, $file_info['modified'] );
        $this->assertEquals( 'test-document', $file_info['normalized_name'] );
    }

    /**
     * Test scanning non-existent directory.
     *
     * @return void
     */
    public function test_scan_non_existent_directory(): void {
        $non_existent_dir = '/path/that/does/not/exist';
        
        $results = $this->file_scanner->scan_directory( $non_existent_dir );
        
        $this->assertIsArray( $results );
        $this->assertEmpty( $results, 'Non-existent directory should return empty array' );
    }

    /**
     * Test scanning multiple directories.
     *
     * @return void
     */
    public function test_scan_multiple_directories(): void {
        // Create second test directory
        $test_dir2 = sys_get_temp_dir() . '/kapl_test2_' . uniqid();
        mkdir( $test_dir2, 0755, true );

        // Create files in both directories
        touch( $this->test_dir . '/file1.pdf' );
        touch( $test_dir2 . '/file2.pdf' );

        $directories = [ $this->test_dir, $test_dir2 ];
        $results = $this->file_scanner->scan_directories( $directories );
        
        $this->assertCount( 2, $results, 'Should find files from both directories' );
        
        $found_files = array_column( $results, 'filename' );
        $this->assertContains( 'file1.pdf', $found_files );
        $this->assertContains( 'file2.pdf', $found_files );

        // Clean up second directory
        unlink( $test_dir2 . '/file2.pdf' );
        rmdir( $test_dir2 );
    }

    /**
     * Test file filtering by extension.
     *
     * @return void
     */
    public function test_file_filtering_by_extension(): void {
        // Create files with different cases
        $files = [
            'document.pdf',
            'REPORT.PDF',
            'guide.Pdf',
            'data.pDf'
        ];

        foreach ( $files as $filename ) {
            touch( $this->test_dir . '/' . $filename );
        }

        $results = $this->file_scanner->scan_directory( $this->test_dir );
        
        $this->assertCount( 4, $results, 'Should find all PDF files regardless of case' );
    }

    /**
     * Clean up after tests.
     *
     * @return void
     */
    protected function tearDown(): void {
        // Clean up test directory
        if ( is_dir( $this->test_dir ) ) {
            $files = glob( $this->test_dir . '/*' );
            foreach ( $files as $file ) {
                if ( is_file( $file ) ) {
                    unlink( $file );
                } elseif ( is_dir( $file ) ) {
                    $nested_files = glob( $file . '/*' );
                    foreach ( $nested_files as $nested_file ) {
                        unlink( $nested_file );
                    }
                    rmdir( $file );
                }
            }
            rmdir( $this->test_dir );
        }
    }
}
