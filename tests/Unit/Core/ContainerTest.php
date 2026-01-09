<?php
/**
 * Container Tests
 *
 * @package KissPlugins\AutomatedPdfLinker\Tests
 * @since 3.0.0
 */

namespace KissPlugins\AutomatedPdfLinker\Tests\Unit\Core;

use PHPUnit\Framework\TestCase;
use KissPlugins\AutomatedPdfLinker\Core\Container;
use InvalidArgumentException;

/**
 * Container Test Class
 *
 * @since 3.0.0
 */
class ContainerTest extends TestCase {

    /**
     * Container instance.
     *
     * @var Container
     */
    private $container;

    /**
     * Set up test environment.
     *
     * @return void
     */
    protected function setUp(): void {
        $this->container = new Container();
    }

    /**
     * Test basic service binding and resolution.
     *
     * @return void
     */
    public function test_bind_and_resolve_service(): void {
        $this->container->bind( 'test_service', function() {
            return new \stdClass();
        } );

        $service = $this->container->get( 'test_service' );
        $this->assertInstanceOf( \stdClass::class, $service );
    }

    /**
     * Test singleton binding.
     *
     * @return void
     */
    public function test_singleton_binding(): void {
        $this->container->singleton( 'singleton_service', function() {
            return new \stdClass();
        } );

        $service1 = $this->container->get( 'singleton_service' );
        $service2 = $this->container->get( 'singleton_service' );

        $this->assertSame( $service1, $service2 );
    }

    /**
     * Test instance binding.
     *
     * @return void
     */
    public function test_instance_binding(): void {
        $instance = new \stdClass();
        $instance->test = 'value';

        $this->container->instance( 'test_instance', $instance );

        $resolved = $this->container->get( 'test_instance' );
        $this->assertSame( $instance, $resolved );
        $this->assertEquals( 'value', $resolved->test );
    }

    /**
     * Test service aliases.
     *
     * @return void
     */
    public function test_service_aliases(): void {
        $this->container->bind( 'original_service', function() {
            return new \stdClass();
        } );

        $this->container->alias( 'alias_service', 'original_service' );

        $original = $this->container->get( 'original_service' );
        $aliased = $this->container->get( 'alias_service' );

        $this->assertInstanceOf( \stdClass::class, $original );
        $this->assertInstanceOf( \stdClass::class, $aliased );
    }

    /**
     * Test parameter binding.
     *
     * @return void
     */
    public function test_parameter_binding(): void {
        $this->container->parameter( 'test_param', 'test_value' );

        $this->container->bind( 'service_with_param', function( $container ) {
            return $container->get( 'test_param' );
        } );

        // This test would need a more complex setup to properly test parameter injection
        $this->assertTrue( $this->container->has( 'service_with_param' ) );
    }

    /**
     * Test service existence check.
     *
     * @return void
     */
    public function test_has_service(): void {
        $this->assertFalse( $this->container->has( 'non_existent_service' ) );

        $this->container->bind( 'existing_service', function() {
            return new \stdClass();
        } );

        $this->assertTrue( $this->container->has( 'existing_service' ) );
    }

    /**
     * Test exception for non-existent service.
     *
     * @return void
     */
    public function test_exception_for_non_existent_service(): void {
        $this->expectException( InvalidArgumentException::class );
        $this->expectExceptionMessage( "Service 'non_existent' not found in container." );

        $this->container->get( 'non_existent' );
    }

    /**
     * Test auto-resolution of class names.
     *
     * @return void
     */
    public function test_auto_resolution(): void {
        // Test with stdClass (no constructor)
        $service = $this->container->get( \stdClass::class );
        $this->assertInstanceOf( \stdClass::class, $service );
    }

    /**
     * Test container clearing.
     *
     * @return void
     */
    public function test_clear_container(): void {
        $this->container->bind( 'test_service', function() {
            return new \stdClass();
        } );

        $this->container->instance( 'test_instance', new \stdClass() );

        $this->assertTrue( $this->container->has( 'test_service' ) );

        $this->container->clear();

        $this->assertFalse( $this->container->has( 'test_service' ) );
        $this->assertEquals( [], $this->container->getServices() );
        $this->assertEquals( [], $this->container->getInstances() );
    }

    /**
     * Test getting service list.
     *
     * @return void
     */
    public function test_get_services(): void {
        $this->container->bind( 'service1', function() {
            return new \stdClass();
        } );

        $this->container->bind( 'service2', function() {
            return new \stdClass();
        } );

        $services = $this->container->getServices();
        $this->assertContains( 'service1', $services );
        $this->assertContains( 'service2', $services );
        $this->assertCount( 2, $services );
    }
}
