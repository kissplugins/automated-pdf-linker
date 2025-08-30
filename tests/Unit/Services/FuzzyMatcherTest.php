<?php
/**
 * Fuzzy Matcher Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Services\FuzzyMatcher;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Fuzzy Matcher Test Class
 *
 * @since 3.0.0
 */
class FuzzyMatcherTest extends TestCase {

    /**
     * Fuzzy matcher instance.
     *
     * @var FuzzyMatcher
     */
    private $fuzzy_matcher;

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
        $this->fuzzy_matcher = new FuzzyMatcher( $this->logger_mock );
    }

    /**
     * Test fuzzy matcher initialization.
     *
     * @return void
     */
    public function test_fuzzy_matcher_initialization(): void {
        $this->assertInstanceOf( FuzzyMatcher::class, $this->fuzzy_matcher );
    }

    /**
     * Test exact string matching.
     *
     * @return void
     */
    public function test_exact_string_matching(): void {
        $search_term = 'user-guide';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'admin-manual.pdf' => [ 'normalized_name' => 'admin-manual' ],
            'installation.pdf' => [ 'normalized_name' => 'installation' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertNotEmpty( $matches );
        $this->assertArrayHasKey( 'user-guide.pdf', $matches );
        $this->assertEquals( 100, $matches['user-guide.pdf']['score'] );
    }

    /**
     * Test partial string matching.
     *
     * @return void
     */
    public function test_partial_string_matching(): void {
        $search_term = 'guide';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'installation-guide.pdf' => [ 'normalized_name' => 'installation-guide' ],
            'quick-start.pdf' => [ 'normalized_name' => 'quick-start' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertNotEmpty( $matches );
        $this->assertArrayHasKey( 'user-guide.pdf', $matches );
        $this->assertArrayHasKey( 'installation-guide.pdf', $matches );
        $this->assertArrayNotHasKey( 'quick-start.pdf', $matches );
    }

    /**
     * Test case insensitive matching.
     *
     * @return void
     */
    public function test_case_insensitive_matching(): void {
        $search_term = 'USER GUIDE';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'admin-manual.pdf' => [ 'normalized_name' => 'admin-manual' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertNotEmpty( $matches );
        $this->assertArrayHasKey( 'user-guide.pdf', $matches );
    }

    /**
     * Test fuzzy matching with typos.
     *
     * @return void
     */
    public function test_fuzzy_matching_with_typos(): void {
        $search_term = 'user-gude'; // Missing 'i' in 'guide'
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'admin-manual.pdf' => [ 'normalized_name' => 'admin-manual' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertNotEmpty( $matches );
        $this->assertArrayHasKey( 'user-guide.pdf', $matches );
        $this->assertGreaterThan( 70, $matches['user-guide.pdf']['score'] );
    }

    /**
     * Test matching with special characters.
     *
     * @return void
     */
    public function test_matching_with_special_characters(): void {
        $search_term = 'user & admin guide';
        $candidates = [
            'user-admin-guide.pdf' => [ 'normalized_name' => 'user-admin-guide' ],
            'user-and-admin-guide.pdf' => [ 'normalized_name' => 'user-and-admin-guide' ],
            'installation.pdf' => [ 'normalized_name' => 'installation' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertNotEmpty( $matches );
        $this->assertArrayHasKey( 'user-admin-guide.pdf', $matches );
        $this->assertArrayHasKey( 'user-and-admin-guide.pdf', $matches );
    }

    /**
     * Test score calculation accuracy.
     *
     * @return void
     */
    public function test_score_calculation_accuracy(): void {
        $search_term = 'user-guide';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],           // Exact match
            'user-guide-v2.pdf' => [ 'normalized_name' => 'user-guide-v2' ],     // Close match
            'installation-guide.pdf' => [ 'normalized_name' => 'installation-guide' ], // Partial match
            'admin-manual.pdf' => [ 'normalized_name' => 'admin-manual' ]         // No match
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        // Exact match should have highest score
        $this->assertEquals( 100, $matches['user-guide.pdf']['score'] );
        
        // Close match should have high score
        $this->assertGreaterThan( 80, $matches['user-guide-v2.pdf']['score'] );
        
        // Partial match should have moderate score
        $this->assertGreaterThan( 50, $matches['installation-guide.pdf']['score'] );
        $this->assertLessThan( 80, $matches['installation-guide.pdf']['score'] );
    }

    /**
     * Test minimum score threshold.
     *
     * @return void
     */
    public function test_minimum_score_threshold(): void {
        $search_term = 'user-guide';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'completely-different.pdf' => [ 'normalized_name' => 'completely-different' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates, 50 );
        
        $this->assertArrayHasKey( 'user-guide.pdf', $matches );
        $this->assertArrayNotHasKey( 'completely-different.pdf', $matches );
    }

    /**
     * Test empty search term.
     *
     * @return void
     */
    public function test_empty_search_term(): void {
        $search_term = '';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'admin-manual.pdf' => [ 'normalized_name' => 'admin-manual' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertEmpty( $matches, 'Empty search term should return no matches' );
    }

    /**
     * Test empty candidates array.
     *
     * @return void
     */
    public function test_empty_candidates_array(): void {
        $search_term = 'user-guide';
        $candidates = [];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertEmpty( $matches, 'Empty candidates should return no matches' );
    }

    /**
     * Test multiple word matching.
     *
     * @return void
     */
    public function test_multiple_word_matching(): void {
        $search_term = 'installation user guide';
        $candidates = [
            'user-guide.pdf' => [ 'normalized_name' => 'user-guide' ],
            'installation-guide.pdf' => [ 'normalized_name' => 'installation-guide' ],
            'user-installation-guide.pdf' => [ 'normalized_name' => 'user-installation-guide' ],
            'admin-manual.pdf' => [ 'normalized_name' => 'admin-manual' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $this->assertNotEmpty( $matches );
        
        // File with all words should have highest score
        $this->assertArrayHasKey( 'user-installation-guide.pdf', $matches );
        $this->assertGreaterThan( 90, $matches['user-installation-guide.pdf']['score'] );
        
        // Files with some words should have lower scores
        $this->assertArrayHasKey( 'user-guide.pdf', $matches );
        $this->assertArrayHasKey( 'installation-guide.pdf', $matches );
    }

    /**
     * Test result sorting by score.
     *
     * @return void
     */
    public function test_result_sorting_by_score(): void {
        $search_term = 'guide';
        $candidates = [
            'admin-guide.pdf' => [ 'normalized_name' => 'admin-guide' ],
            'guide.pdf' => [ 'normalized_name' => 'guide' ],
            'user-guide-v2.pdf' => [ 'normalized_name' => 'user-guide-v2' ]
        ];

        $matches = $this->fuzzy_matcher->find_matches( $search_term, $candidates );
        
        $scores = array_column( $matches, 'score' );
        $sorted_scores = $scores;
        rsort( $sorted_scores );
        
        $this->assertEquals( $sorted_scores, $scores, 'Results should be sorted by score in descending order' );
    }
}
