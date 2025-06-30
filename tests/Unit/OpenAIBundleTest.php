<?php

namespace OpenAIBundle\Tests\Unit;

use OpenAIBundle\OpenAIBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OpenAIBundleTest extends TestCase
{
    public function testBundleInstantiation(): void
    {
        $bundle = new OpenAIBundle();
        $this->assertInstanceOf(OpenAIBundle::class, $bundle);
    }

    public function testBuild(): void
    {
        $bundle = new OpenAIBundle();
        $container = new ContainerBuilder();
        
        // Should not throw exception
        $bundle->build($container);
        $this->assertTrue(true);
    }
}