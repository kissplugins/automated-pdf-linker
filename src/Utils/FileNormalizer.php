<?php
/**
 * File Normalizer Utility
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Utils;

/**
 * File Normalizer Class
 *
 * Handles filename normalization for fuzzy matching.
 *
 * @since 3.0.0
 */
class FileNormalizer {

    /**
     * Normalizes a filename or product title into a "slug-style" string.
     *
     * Product titles like "3.5 Gram THCA Disposable Vape (Limited Run) – Pressure"
     * and file names like "3.5 Gram THCA Disposable Vape (Limited Run) – Pressure.pdf"
     * both become "3-5-gram-thca-disposable-vape-limited-run-pressure"
     *
     * Normalization steps:
     *  1. Split camelCase words by inserting a space before any upper-case letter
     *     that follows a lower-case letter (e.g. "BlueBerry" → "Blue Berry").
     *  2. Lower-case the whole string.
     *  3. Strip the file extension, if present.
     *  4. Replace every run of non-alphanumeric characters with a single dash.
     *  5. Trim leading/trailing dashes.
     *
     * @since 3.0.0
     * @param string $filename Raw file name or product title.
     * @return string Slug-style string suitable for matching.
     */
    public static function normalize( string $filename ): string {
        if ( empty( $filename ) ) {
            return '';
        }

        // 1 - split camelCase boundaries to ensure consistent word breaks.
        $filename = preg_replace( '/([a-z])([A-Z])/', '$1 $2', $filename );

        // 2 - lower-case for case-insensitive matching.
        $filename = strtolower( $filename );

        // 3 - remove the file extension, if any.
        $filename = pathinfo( $filename, PATHINFO_FILENAME );

        // 4 - collapse all runs of non-alphanumeric chars (spaces, punctuation,
        //     en/em dashes, parentheses, dots, etc.) to a single "-".
        $filename = preg_replace( '/[^a-z0-9]+/', '-', $filename );

        // 5 - trim stray leading/trailing dashes.
        $filename = trim( $filename, '-' );

        return $filename;
    }

    /**
     * Normalize filename with logging.
     *
     * @param string $filename Raw filename.
     * @param Logger $logger   Logger instance.
     * @return string Normalized filename.
     */
    public static function normalize_with_logging( string $filename, Logger $logger ): string {
        $normalized = self::normalize( $filename );
        $logger->debug( "Normalized filename: '{$filename}' -> '{$normalized}'" );
        return $normalized;
    }
}
