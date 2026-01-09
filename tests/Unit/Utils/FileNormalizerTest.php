<?php
/**
 * File Normalizer Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Utils\FileNormalizer;

/**
 * File Normalizer Test Class
 *
 * @since 3.0.0
 */
class FileNormalizerTest extends TestCase {

    /**
     * Test basic filename normalization.
     *
     * @return void
     */
    public function test_normalize_basic_filename(): void {
        $input = 'Test Document.pdf';
        $expected = 'test-document';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test camelCase splitting.
     *
     * @return void
     */
    public function test_normalize_camel_case(): void {
        $input = 'BlueBerryStrain.pdf';
        $expected = 'blue-berry-strain';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test complex filename with special characters.
     *
     * @return void
     */
    public function test_normalize_complex_filename(): void {
        $input = '3.5 Gram THCA Disposable Vape (Limited Run) – Pressure.pdf';
        $expected = '3-5-gram-thca-disposable-vape-limited-run-pressure';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test empty string handling.
     *
     * @return void
     */
    public function test_normalize_empty_string(): void {
        $input = '';
        $expected = '';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test filename without extension.
     *
     * @return void
     */
    public function test_normalize_no_extension(): void {
        $input = 'Test Document';
        $expected = 'test-document';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test multiple consecutive special characters.
     *
     * @return void
     */
    public function test_normalize_multiple_special_chars(): void {
        $input = 'Test---Document...Final!!!.pdf';
        $expected = 'test-document-final';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }

    /**
     * Test leading and trailing special characters.
     *
     * @return void
     */
    public function test_normalize_leading_trailing_chars(): void {
        $input = '---Test Document---.pdf';
        $expected = 'test-document';
        $actual = FileNormalizer::normalize( $input );
        
        $this->assertEquals( $expected, $actual );
    }
}
