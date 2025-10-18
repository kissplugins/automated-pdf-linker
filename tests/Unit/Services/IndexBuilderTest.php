<?php
/**
 * Index Builder Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Services\IndexBuilder;
use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Index Builder Test Class
 *
 * @since 3.0.0
 */
class IndexBuilderTest extends TestCase {

    /**
     * Index builder instance.
     *
     * @var IndexBuilder
     */
    private $index_builder;

    /**
     * Cache manager mock.
     *
     * @var CacheManager
     */
    private $cache_manager_mock;

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
        $this->cache_manager_mock = $this->createMock( CacheManager::class );
        $this->logger_mock = $this->createMock( Logger::class );
        $this->index_builder = new IndexBuilder( $this->cache_manager_mock, $this->logger_mock );
        
        // Create temporary test directory
        $this->test_dir = sys_get_temp_dir() . '/kapl_index_test_' . uniqid();
        mkdir( $this->test_dir, 0755, true );
    }

    /**
     * Test index builder initialization.
     *
     * @return void
     */
    public function test_index_builder_initialization(): void {
        $this->assertInstanceOf( IndexBuilder::class, $this->index_builder );
    }

    /**
     * Test building index from empty directory.
     *
     * @return void
     */
    public function test_build_index_empty_directory(): void {
        $directories = [ $this->test_dir ];
        
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'update_index' )
            ->with( [] )
            ->willReturn( true );

        $result = $this->index_builder->build_index( $directories );
        
        $this->assertTrue( $result );
    }

    /**
     * Test building index with PDF files.
     *
     * @return void
     */
    public function test_build_index_with_pdf_files(): void {
        // Create test PDF files
        $test_files = [
            'document1.pdf',
            'report-2023.pdf',
            'user_guide.pdf'
        ];

        foreach ( $test_files as $filename ) {
            file_put_contents( $this->test_dir . '/' . $filename, 'Test PDF content' );
        }

        $directories = [ $this->test_dir ];
        
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'update_index' )
            ->with( $this->callback( function( $index_data ) {
                return is_array( $index_data ) && count( $index_data ) === 3;
            } ) )
            ->willReturn( true );

        $result = $this->index_builder->build_index( $directories );
        
        $this->assertTrue( $result );
    }

    /**
     * Test getting current index.
     *
     * @return void
     */
    public function test_get_current_index(): void {
        $mock_index = [
            'test.pdf' => [
                'path' => '/test/test.pdf',
                'filename' => 'test.pdf',
                'normalized_name' => 'test',
                'size' => 1024
            ]
        ];

        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'get_index' )
            ->willReturn( $mock_index );

        $result = $this->index_builder->get_current_index();
        
        $this->assertEquals( $mock_index, $result );
    }

    /**
     * Test getting index statistics.
     *
     * @return void
     */
    public function test_get_index_statistics(): void {
        $mock_index = [
            'small.pdf' => [
                'path' => '/test/small.pdf',
                'filename' => 'small.pdf',
                'normalized_name' => 'small',
                'size' => 1024
            ],
            'large.pdf' => [
                'path' => '/test/large.pdf',
                'filename' => 'large.pdf',
                'normalized_name' => 'large',
                'size' => 5120
            ]
        ];

        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'get_index' )
            ->willReturn( $mock_index );

        $stats = $this->index_builder->get_index_statistics();
        
        $this->assertIsArray( $stats );
        $this->assertArrayHasKey( 'total_files', $stats );
        $this->assertArrayHasKey( 'total_size', $stats );
        $this->assertArrayHasKey( 'average_size', $stats );
        
        $this->assertEquals( 2, $stats['total_files'] );
        $this->assertEquals( 6144, $stats['total_size'] );
        $this->assertEquals( 3072, $stats['average_size'] );
    }

    /**
     * Test clearing index.
     *
     * @return void
     */
    public function test_clear_index(): void {
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'clear_index' )
            ->willReturn( true );

        $result = $this->index_builder->clear_index();
        
        $this->assertTrue( $result );
    }

    /**
     * Test building index with multiple directories.
     *
     * @return void
     */
    public function test_build_index_multiple_directories(): void {
        // Create second test directory
        $test_dir2 = sys_get_temp_dir() . '/kapl_index_test2_' . uniqid();
        mkdir( $test_dir2, 0755, true );

        // Create files in both directories
        file_put_contents( $this->test_dir . '/file1.pdf', 'Content 1' );
        file_put_contents( $test_dir2 . '/file2.pdf', 'Content 2' );

        $directories = [ $this->test_dir, $test_dir2 ];
        
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'update_index' )
            ->with( $this->callback( function( $index_data ) {
                return is_array( $index_data ) && count( $index_data ) === 2;
            } ) )
            ->willReturn( true );

        $result = $this->index_builder->build_index( $directories );
        
        $this->assertTrue( $result );

        // Clean up second directory
        unlink( $test_dir2 . '/file2.pdf' );
        rmdir( $test_dir2 );
    }

    /**
     * Test building index with non-existent directory.
     *
     * @return void
     */
    public function test_build_index_non_existent_directory(): void {
        $directories = [ '/path/that/does/not/exist' ];
        
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'update_index' )
            ->with( [] )
            ->willReturn( true );

        $result = $this->index_builder->build_index( $directories );
        
        $this->assertTrue( $result );
    }

    /**
     * Test index building failure handling.
     *
     * @return void
     */
    public function test_index_building_failure_handling(): void {
        $directories = [ $this->test_dir ];
        
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'update_index' )
            ->willReturn( false );

        $result = $this->index_builder->build_index( $directories );
        
        $this->assertFalse( $result );
    }

    /**
     * Test getting statistics for empty index.
     *
     * @return void
     */
    public function test_get_statistics_empty_index(): void {
        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'get_index' )
            ->willReturn( null );

        $stats = $this->index_builder->get_index_statistics();
        
        $this->assertIsArray( $stats );
        $this->assertEquals( 0, $stats['total_files'] );
        $this->assertEquals( 0, $stats['total_size'] );
        $this->assertEquals( 0, $stats['average_size'] );
    }

    /**
     * Test index validation.
     *
     * @return void
     */
    public function test_index_validation(): void {
        $valid_index = [
            'test.pdf' => [
                'path' => '/test/test.pdf',
                'filename' => 'test.pdf',
                'normalized_name' => 'test',
                'size' => 1024
            ]
        ];

        $this->cache_manager_mock
            ->expects( $this->once() )
            ->method( 'get_index' )
            ->willReturn( $valid_index );

        $is_valid = $this->index_builder->validate_index();
        
        $this->assertTrue( $is_valid );
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
                }
            }
            rmdir( $this->test_dir );
        }
    }
}
