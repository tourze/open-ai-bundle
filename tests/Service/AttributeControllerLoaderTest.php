<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Service\AttributeControllerLoader;
use PHPUnit\Framework\TestCase;

class AttributeControllerLoaderTest extends TestCase
{
    public function testServiceCanBeInstantiated(): void
    {
        $service = new AttributeControllerLoader();
        
        $this->assertInstanceOf(AttributeControllerLoader::class, $service);
    }
}