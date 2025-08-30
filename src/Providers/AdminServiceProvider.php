<?php
/**
 * Admin Service Provider
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Providers;

use KissPlugins\AutomatedPdfLinker\Core\ServiceProvider;
use KissPlugins\AutomatedPdfLinker\Admin\Settings;
use KissPlugins\AutomatedPdfLinker\Admin\AdminMenu;
use KissPlugins\AutomatedPdfLinker\Admin\SelfTestPage;
use KissPlugins\AutomatedPdfLinker\Admin\SelfTest;
use KissPlugins\AutomatedPdfLinker\Services\IndexBuilder;
use KissPlugins\AutomatedPdfLinker\Utils\Logger;

/**
 * Admin Service Provider Class
 *
 * Registers admin-related services.
 *
 * @since 3.0.0
 */
class AdminServiceProvider extends ServiceProvider {

    /**
     * Register admin services.
     *
     * @return void
     */
    public function register(): void {
        // Register Settings as singleton
        $this->container->singleton( 'settings', function( $container ) {
            return new Settings( 
                $container->get( IndexBuilder::class ),
                $container->get( Logger::class )
            );
        } );

        $this->container->singleton( Settings::class, function( $container ) {
            return $container->get( 'settings' );
        } );

        // Register AdminMenu
        $this->container->bind( 'admin_menu', function( $container ) {
            return new AdminMenu( $container->get( Settings::class ) );
        } );

        $this->container->bind( AdminMenu::class, function( $container ) {
            return $container->get( 'admin_menu' );
        } );

        // Register SelfTest
        $this->container->bind( 'self_test', function( $container ) {
            // SelfTest needs the main plugin instance, which we'll resolve later
            return new SelfTest( $container->get( 'plugin' ) );
        } );

        $this->container->bind( SelfTest::class, function( $container ) {
            return $container->get( 'self_test' );
        } );

        // Register SelfTestPage
        $this->container->bind( 'self_test_page', function( $container ) {
            return new SelfTestPage( $container->get( 'plugin' ) );
        } );

        $this->container->bind( SelfTestPage::class, function( $container ) {
            return $container->get( 'self_test_page' );
        } );
    }
}
