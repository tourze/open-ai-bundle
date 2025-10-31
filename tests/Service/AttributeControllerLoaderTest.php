<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Service\AttributeControllerLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $service = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(AttributeControllerLoader::class, $service);

        $routeCollection = $service->autoload();
        $this->assertInstanceOf(RouteCollection::class, $routeCollection);
    }

    public function testLoadCallsAutoload(): void
    {
        $service = self::getService(AttributeControllerLoader::class);

        $routeCollection = $service->load('resource', 'type');
        $this->assertInstanceOf(RouteCollection::class, $routeCollection);
    }

    public function testSupportsReturnsFalse(): void
    {
        $service = self::getService(AttributeControllerLoader::class);

        $this->assertFalse($service->supports('resource', 'type'));
    }
}
