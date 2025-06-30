<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\FindReferences;
use PHPUnit\Framework\TestCase;

class FindReferencesTest extends TestCase
{
    private FindReferences $function;

    protected function setUp(): void
    {
        $this->function = new FindReferences();
    }

    public function testGetName(): void
    {
        $this->assertEquals('FindReferences', $this->function->getName());
    }

    public function testGetDescription(): void
    {
        $description = $this->function->getDescription();
        $this->assertNotEmpty($description);
    }

    public function testGetParameters(): void
    {
        $parameters = iterator_to_array($this->function->getParameters());
        $this->assertNotNull($parameters);
    }

    public function testImplementsToolInterface(): void
    {
        $this->assertInstanceOf(\Tourze\MCPContracts\ToolInterface::class, $this->function);
    }
}