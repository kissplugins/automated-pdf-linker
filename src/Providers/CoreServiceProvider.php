<?php
/**
 * Core Service Provider
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Providers;

use KissPlugins\AutomatedPdfLinker\Core\ServiceProvider;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;
use KissPlugins\AutomatedPdfLinker\Utils\FileNormalizer;

/**
 * Core Service Provider Class
 *
 * Registers core utility services.
 *
 * @since 3.0.0
 */
class CoreServiceProvider extends ServiceProvider {

    /**
     * Register core services.
     *
     * @return void
     */
    public function register(): void {
        // Register Logger as singleton
        $this->container->singleton( 'logger', function( $container ) {
            return new Logger();
        } );

        // Register Logger by class name
        $this->container->singleton( Logger::class, function( $container ) {
            return $container->get( 'logger' );
        } );

        // Register FileNormalizer (stateless utility)
        $this->container->bind( 'file_normalizer', function( $container ) {
            return new FileNormalizer();
        } );

        $this->container->bind( FileNormalizer::class, function( $container ) {
            return $container->get( 'file_normalizer' );
        } );

        // Register plugin configuration parameters
        $this->container->parameter( 'plugin_file', $this->getPluginFile() );
        $this->container->parameter( 'plugin_dir', $this->getPluginDir() );
        $this->container->parameter( 'plugin_url', $this->getPluginUrl() );
        $this->container->parameter( 'plugin_version', '3.0.0' );
    }

    /**
     * Get plugin file path.
     *
     * @return string Plugin file path.
     */
    private function getPluginFile(): string {
        return \plugin_dir_path( dirname( dirname( __DIR__ ) ) ) . 'kiss-automated-pdf-linker-v3.php';
    }

    /**
     * Get plugin directory path.
     *
     * @return string Plugin directory path.
     */
    private function getPluginDir(): string {
        return \plugin_dir_path( $this->getPluginFile() );
    }

    /**
     * Get plugin URL.
     *
     * @return string Plugin URL.
     */
    private function getPluginUrl(): string {
        return \plugin_dir_url( $this->getPluginFile() );
    }
}
