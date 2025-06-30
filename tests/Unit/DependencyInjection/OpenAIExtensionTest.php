<?php

namespace OpenAIBundle\Tests\Unit\DependencyInjection;

use OpenAIBundle\DependencyInjection\OpenAIExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class OpenAIExtensionTest extends TestCase
{
    public function testExtensionInstantiation(): void
    {
        $extension = new OpenAIExtension();
        $this->assertInstanceOf(OpenAIExtension::class, $extension);
    }

    public function testLoad(): void
    {
        $extension = new OpenAIExtension();
        $container = new ContainerBuilder();
        
        // Should not throw exception
        $extension->load([], $container);
        $this->assertTrue(true);
    }
}