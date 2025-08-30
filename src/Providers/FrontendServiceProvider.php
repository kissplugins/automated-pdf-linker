<?php
/**
 * Frontend Service Provider
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Providers;

use KissPlugins\AutomatedPdfLinker\Core\ServiceProvider;
use KissPlugins\AutomatedPdfLinker\Frontend\Shortcode;
use KissPlugins\AutomatedPdfLinker\Services\CacheManager;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Frontend Service Provider Class
 *
 * Registers frontend-related services.
 *
 * @since 3.0.0
 */
class FrontendServiceProvider extends ServiceProvider {

    /**
     * Register frontend services.
     *
     * @return void
     */
    public function register(): void {
        // Register Shortcode as singleton
        $this->container->singleton( 'shortcode', function( $container ) {
            return new Shortcode( 
                $container->get( CacheManager::class ),
                $container->get( Logger::class )
            );
        } );

        $this->container->singleton( Shortcode::class, function( $container ) {
            return $container->get( 'shortcode' );
        } );
    }
}
