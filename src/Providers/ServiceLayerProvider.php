<?php
/**
 * Service Layer Provider
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Providers;

use KissPlugins\AutomatedPdfLinker\Core\ServiceProvider;
use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Services\FileScanner;
use KissPlugins\AutomatedPdfLinker\Services\FuzzyMatcher;
use KissPlugins\AutomatedPdfLinker\Services\IndexBuilder;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Service Layer Provider Class
 *
 * Registers business logic services.
 *
 * @since 3.0.0
 */
class ServiceLayerProvider extends ServiceProvider {

    /**
     * Register service layer services.
     *
     * @return void
     */
    public function register(): void {
        // Register CacheManager as singleton
        $this->container->singleton( 'cache_manager', function( $container ) {
            return new CacheManager( $container->get( Logger::class ) );
        } );

        $this->container->singleton( CacheManager::class, function( $container ) {
            return $container->get( 'cache_manager' );
        } );

        // Register FileScanner
        $this->container->bind( 'file_scanner', function( $container ) {
            return new FileScanner( $container->get( Logger::class ) );
        } );

        $this->container->bind( FileScanner::class, function( $container ) {
            return $container->get( 'file_scanner' );
        } );

        // Register FuzzyMatcher
        $this->container->bind( 'fuzzy_matcher', function( $container ) {
            return new FuzzyMatcher( $container->get( Logger::class ) );
        } );

        $this->container->bind( FuzzyMatcher::class, function( $container ) {
            return $container->get( 'fuzzy_matcher' );
        } );

        // Register IndexBuilder as singleton
        $this->container->singleton( 'index_builder', function( $container ) {
            return new IndexBuilder( 
                $container->get( CacheManager::class ),
                $container->get( Logger::class )
            );
        } );

        $this->container->singleton( IndexBuilder::class, function( $container ) {
            return $container->get( 'index_builder' );
        } );
    }
}
