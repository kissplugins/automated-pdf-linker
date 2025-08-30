<?php
/**
 * Dependency Injection Container
 *
 * @package KissPlugins\AutomatedPdfLinker
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Core;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionParameter;

/**
 * Container Class
 *
 * Advanced dependency injection container with automatic resolution,
 * singleton management, and service configuration.
 *
 * @since 3.0.0
 */
class Container {

    /**
     * Service definitions.
     *
     * @var array
     */
    private $services = [];

    /**
     * Singleton instances.
     *
     * @var array
     */
    private $instances = [];

    /**
     * Service aliases.
     *
     * @var array
     */
    private $aliases = [];

    /**
     * Service parameters.
     *
     * @var array
     */
    private $parameters = [];

    /**
     * Bind a service to the container.
     *
     * @param string   $id       Service identifier.
     * @param callable|string $concrete Service implementation.
     * @param bool     $singleton Whether to treat as singleton.
     * @return void
     */
    public function bind( string $id, $concrete, bool $singleton = false ): void {
        $this->services[ $id ] = [
            'concrete'  => $concrete,
            'singleton' => $singleton,
        ];
    }

    /**
     * Bind a singleton service to the container.
     *
     * @param string   $id       Service identifier.
     * @param callable|string $concrete Service implementation.
     * @return void
     */
    public function singleton( string $id, $concrete ): void {
        $this->bind( $id, $concrete, true );
    }

    /**
     * Bind an existing instance as a singleton.
     *
     * @param string $id       Service identifier.
     * @param mixed  $instance Service instance.
     * @return void
     */
    public function instance( string $id, $instance ): void {
        $this->instances[ $id ] = $instance;
    }

    /**
     * Create an alias for a service.
     *
     * @param string $alias   Alias name.
     * @param string $service Original service identifier.
     * @return void
     */
    public function alias( string $alias, string $service ): void {
        $this->aliases[ $alias ] = $service;
    }

    /**
     * Set a parameter value.
     *
     * @param string $key   Parameter key.
     * @param mixed  $value Parameter value.
     * @return void
     */
    public function parameter( string $key, $value ): void {
        $this->parameters[ $key ] = $value;
    }

    /**
     * Resolve a service from the container.
     *
     * @param string $id Service identifier.
     * @return mixed Resolved service instance.
     * @throws InvalidArgumentException If service cannot be resolved.
     */
    public function get( string $id ) {
        // Check for alias
        if ( isset( $this->aliases[ $id ] ) ) {
            $id = $this->aliases[ $id ];
        }

        // Return existing singleton instance
        if ( isset( $this->instances[ $id ] ) ) {
            return $this->instances[ $id ];
        }

        // Check if service is bound
        if ( isset( $this->services[ $id ] ) ) {
            $service = $this->services[ $id ];
            $instance = $this->resolve( $service['concrete'] );

            // Store singleton instance
            if ( $service['singleton'] ) {
                $this->instances[ $id ] = $instance;
            }

            return $instance;
        }

        // Try auto-resolution for class names
        if ( \class_exists( $id ) ) {
            $instance = $this->resolve( $id );

            return $instance;
        }

        throw new InvalidArgumentException( "Service '{$id}' not found in container." );
    }

    /**
     * Check if a service exists in the container.
     *
     * @param string $id Service identifier.
     * @return bool True if service exists, false otherwise.
     */
    public function has( string $id ): bool {
        // Check for alias
        if ( isset( $this->aliases[ $id ] ) ) {
            $id = $this->aliases[ $id ];
        }

        return isset( $this->instances[ $id ] ) || 
               isset( $this->services[ $id ] ) || 
               \class_exists( $id );
    }

    /**
     * Resolve a concrete service implementation.
     *
     * @param callable|string $concrete Service implementation.
     * @return mixed Resolved instance.
     * @throws InvalidArgumentException If resolution fails.
     */
    private function resolve( $concrete ) {
        // Handle callable/closure
        if ( \is_callable( $concrete ) ) {
            return $concrete( $this );
        }

        // Handle class name
        if ( \is_string( $concrete ) && \class_exists( $concrete ) ) {
            return $this->build( $concrete );
        }

        throw new InvalidArgumentException( "Cannot resolve service: " . \gettype( $concrete ) );
    }

    /**
     * Build a class instance with dependency injection.
     *
     * @param string $class Class name to build.
     * @return object Built class instance.
     * @throws InvalidArgumentException If class cannot be built.
     */
    private function build( string $class ) {
        try {
            $reflection = new ReflectionClass( $class );

            // Check if class is instantiable
            if ( ! $reflection->isInstantiable() ) {
                throw new InvalidArgumentException( "Class '{$class}' is not instantiable." );
            }

            $constructor = $reflection->getConstructor();

            // No constructor, simple instantiation
            if ( null === $constructor ) {
                return new $class();
            }

            // Resolve constructor dependencies
            $dependencies = $this->resolveDependencies( $constructor->getParameters() );

            return $reflection->newInstanceArgs( $dependencies );

        } catch ( ReflectionException $e ) {
            throw new InvalidArgumentException( "Cannot build class '{$class}': " . $e->getMessage() );
        }
    }

    /**
     * Resolve method/constructor dependencies.
     *
     * @param ReflectionParameter[] $parameters Method parameters.
     * @return array Resolved dependencies.
     * @throws InvalidArgumentException If dependency cannot be resolved.
     */
    private function resolveDependencies( array $parameters ): array {
        $dependencies = [];

        foreach ( $parameters as $parameter ) {
            $dependency = $this->resolveDependency( $parameter );
            $dependencies[] = $dependency;
        }

        return $dependencies;
    }

    /**
     * Resolve a single dependency.
     *
     * @param ReflectionParameter $parameter Parameter to resolve.
     * @return mixed Resolved dependency.
     * @throws InvalidArgumentException If dependency cannot be resolved.
     */
    private function resolveDependency( ReflectionParameter $parameter ) {
        $name = $parameter->getName();
        $type = $parameter->getType();

        // Check for parameter override
        if ( isset( $this->parameters[ $name ] ) ) {
            return $this->parameters[ $name ];
        }

        // Handle typed parameters
        if ( null !== $type && ! $type->isBuiltin() ) {
            $className = $type->getName();
            
            try {
                return $this->get( $className );
            } catch ( InvalidArgumentException $e ) {
                // Continue to default value check
            }
        }

        // Use default value if available
        if ( $parameter->isDefaultValueAvailable() ) {
            return $parameter->getDefaultValue();
        }

        // Handle nullable parameters
        if ( $parameter->allowsNull() ) {
            return null;
        }

        throw new InvalidArgumentException( 
            "Cannot resolve dependency '{$name}' for parameter in " . 
            $parameter->getDeclaringClass()->getName() 
        );
    }

    /**
     * Call a method with dependency injection.
     *
     * @param callable|array $callback Method to call.
     * @param array          $parameters Additional parameters.
     * @return mixed Method result.
     * @throws InvalidArgumentException If method cannot be called.
     */
    public function call( $callback, array $parameters = [] ) {
        // Store additional parameters
        $originalParameters = $this->parameters;
        $this->parameters = \array_merge( $this->parameters, $parameters );

        try {
            if ( \is_callable( $callback ) ) {
                // Handle closure or function
                if ( $callback instanceof \Closure ) {
                    $reflection = new \ReflectionFunction( $callback );
                    $dependencies = $this->resolveDependencies( $reflection->getParameters() );
                    return $callback( ...$dependencies );
                }

                // Handle array callback [object, method] or [class, method]
                if ( \is_array( $callback ) && \count( $callback ) === 2 ) {
                    [ $class, $method ] = $callback;

                    if ( \is_string( $class ) ) {
                        $class = $this->get( $class );
                    }

                    $reflection = new \ReflectionMethod( $class, $method );
                    $dependencies = $this->resolveDependencies( $reflection->getParameters() );
                    return $class->$method( ...$dependencies );
                }
            }

            throw new InvalidArgumentException( "Invalid callback provided." );

        } finally {
            // Restore original parameters
            $this->parameters = $originalParameters;
        }
    }

    /**
     * Get all registered service identifiers.
     *
     * @return array Service identifiers.
     */
    public function getServices(): array {
        return \array_keys( $this->services );
    }

    /**
     * Get all singleton instances.
     *
     * @return array Singleton instances.
     */
    public function getInstances(): array {
        return $this->instances;
    }

    /**
     * Clear all services and instances (useful for testing).
     *
     * @return void
     */
    public function clear(): void {
        $this->services = [];
        $this->instances = [];
        $this->aliases = [];
        $this->parameters = [];
    }
}
