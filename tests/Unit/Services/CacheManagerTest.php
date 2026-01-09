<?php
/**
 * Cache Manager Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Cache Manager Test Class
 *
 * @since 3.0.0
 */
class CacheManagerTest extends TestCase {

    /**
     * Cache manager instance.
     *
     * @var CacheManager
     */
    private $cache_manager;

    /**
     * Logger mock.
     *
     * @var Logger
     */
    private $logger_mock;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->logger_mock = $this->createMock( Logger::class );
        $this->cache_manager = new CacheManager( $this->logger_mock );
    }

    /**
     * Test cache manager initialization.
     *
     * @return void
     */
    public function test_cache_manager_initialization(): void {
        $this->assertInstanceOf( CacheManager::class, $this->cache_manager );
    }

    /**
     * Test update and get index with small dataset.
     *
     * @return void
     */
    public function test_update_and_get_index_small_dataset(): void {
        $test_data = [
            'test1.pdf' => [
                'path' => '/test/test1.pdf',
                'filename' => 'test1.pdf',
                'normalized_name' => 'test1',
                'size' => 1024
            ],
            'test2.pdf' => [
                'path' => '/test/test2.pdf',
                'filename' => 'test2.pdf',
                'normalized_name' => 'test2',
                'size' => 2048
            ]
        ];

        // Test update
        $result = $this->cache_manager->update_index( $test_data );
        $this->assertTrue( $result, 'Update index should return true' );

        // Test get
        $retrieved_data = $this->cache_manager->get_index();
        $this->assertNotNull( $retrieved_data, 'Retrieved data should not be null' );
        $this->assertCount( 2, $retrieved_data, 'Should retrieve 2 items' );
        $this->assertArrayHasKey( 'test1.pdf', $retrieved_data );
        $this->assertArrayHasKey( 'test2.pdf', $retrieved_data );
        $this->assertEquals( 'test1', $retrieved_data['test1.pdf']['normalized_name'] );
    }

    /**
     * Test clear index functionality.
     *
     * @return void
     */
    public function test_clear_index(): void {
        // First add some data
        $test_data = [
            'clear_test.pdf' => [
                'path' => '/test/clear_test.pdf',
                'filename' => 'clear_test.pdf',
                'normalized_name' => 'clear-test',
                'size' => 512
            ]
        ];

        $this->cache_manager->update_index( $test_data );
        
        // Verify data exists
        $data_before_clear = $this->cache_manager->get_index();
        $this->assertNotEmpty( $data_before_clear );

        // Clear the index
        $clear_result = $this->cache_manager->clear_index();
        $this->assertTrue( $clear_result, 'Clear index should return true' );

        // Verify data is cleared
        $data_after_clear = $this->cache_manager->get_index();
        $this->assertTrue( 
            $data_after_clear === null || empty( $data_after_clear ),
            'Index should be empty after clearing'
        );
    }

    /**
     * Test empty index handling.
     *
     * @return void
     */
    public function test_empty_index_handling(): void {
        // Clear any existing data
        $this->cache_manager->clear_index();

        // Try to get empty index
        $empty_data = $this->cache_manager->get_index();
        $this->assertTrue( 
            $empty_data === null || empty( $empty_data ),
            'Empty index should return null or empty array'
        );
    }

    /**
     * Test update with empty data.
     *
     * @return void
     */
    public function test_update_with_empty_data(): void {
        $empty_data = [];
        
        $result = $this->cache_manager->update_index( $empty_data );
        $this->assertTrue( $result, 'Update with empty data should succeed' );

        $retrieved_data = $this->cache_manager->get_index();
        $this->assertTrue( 
            $retrieved_data === null || empty( $retrieved_data ),
            'Retrieved data should be empty'
        );
    }

    /**
     * Test data integrity with special characters.
     *
     * @return void
     */
    public function test_data_integrity_special_characters(): void {
        $test_data = [
            'special-chars.pdf' => [
                'path' => '/test/special chars & symbols.pdf',
                'filename' => 'special chars & symbols.pdf',
                'normalized_name' => 'special-chars-symbols',
                'size' => 1536,
                'metadata' => [
                    'title' => 'Test with "quotes" and \'apostrophes\'',
                    'description' => 'Unicode: café, naïve, résumé'
                ]
            ]
        ];

        $this->cache_manager->update_index( $test_data );
        $retrieved_data = $this->cache_manager->get_index();

        $this->assertArrayHasKey( 'special-chars.pdf', $retrieved_data );
        $retrieved_item = $retrieved_data['special-chars.pdf'];
        
        $this->assertEquals( 'special chars & symbols.pdf', $retrieved_item['filename'] );
        $this->assertEquals( 'special-chars-symbols', $retrieved_item['normalized_name'] );
        $this->assertEquals( 'Test with "quotes" and \'apostrophes\'', $retrieved_item['metadata']['title'] );
    }

    /**
     * Test large dataset handling.
     *
     * @return void
     */
    public function test_large_dataset_handling(): void {
        // Create a larger dataset to test compression/file storage
        $large_data = [];
        for ( $i = 1; $i <= 100; $i++ ) {
            $large_data["file{$i}.pdf"] = [
                'path' => "/test/file{$i}.pdf",
                'filename' => "file{$i}.pdf",
                'normalized_name' => "file{$i}",
                'size' => rand( 1024, 10240 ),
                'metadata' => [
                    'title' => "Test Document {$i}",
                    'description' => str_repeat( "This is test content for file {$i}. ", 10 )
                ]
            ];
        }

        $result = $this->cache_manager->update_index( $large_data );
        $this->assertTrue( $result, 'Large dataset update should succeed' );

        $retrieved_data = $this->cache_manager->get_index();
        $this->assertNotNull( $retrieved_data );
        $this->assertCount( 100, $retrieved_data, 'Should retrieve all 100 items' );
        
        // Verify a few random items
        $this->assertArrayHasKey( 'file1.pdf', $retrieved_data );
        $this->assertArrayHasKey( 'file50.pdf', $retrieved_data );
        $this->assertArrayHasKey( 'file100.pdf', $retrieved_data );
    }

    /**
     * Clean up after tests.
     *
     * @return void
     */
    protected function tearDown(): void {
        // Clean up any test data
        if ( $this->cache_manager ) {
            $this->cache_manager->clear_index();
        }
    }
}
