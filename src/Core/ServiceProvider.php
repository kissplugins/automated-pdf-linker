<?php
/**
 * Service Provider Abstract Class
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Core;

/**
 * ServiceProvider Abstract Class
 *
 * Base class for organizing service registrations.
 *
 * @since 3.0.0
 */
abstract class ServiceProvider {

    /**
     * Container instance.
     *
     * @var Container
     */
    protected $container;

    /**
     * Constructor.
     *
     * @param Container $container Container instance.
     */
    public function __construct( Container $container ) {
        $this->container = $container;
    }

    /**
     * Register services in the container.
     *
     * @return void
     */
    abstract public function register(): void;

    /**
     * Boot services after all providers are registered.
     *
     * @return void
     */
    public function boot(): void {
        // Override in child classes if needed
    }
}
