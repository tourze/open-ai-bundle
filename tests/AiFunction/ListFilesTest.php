<?php

namespace OpenAIBundle\Tests\AiFunction;

use OpenAIBundle\AiFunction\ListFiles;
use PHPUnit\Framework\TestCase;

class ListFilesTest extends TestCase
{
    private ListFiles $function;

    protected function setUp(): void
    {
        $this->function = new ListFiles();
    }

    public function testGetName(): void
    {
        $this->assertEquals('ListFiles', $this->function->getName());
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