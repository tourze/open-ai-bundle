<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\FunctionDefinition;
use PHPUnit\Framework\TestCase;

class FunctionDefinitionTest extends TestCase
{
    public function testCreateFunctionDefinition(): void
    {
        $name = 'test_function';
        $description = 'Test description';
        $definition = new FunctionDefinition($name, $description);

        $this->assertInstanceOf(FunctionDefinition::class, $definition);
        $this->assertEquals($name, $definition->getName());
        $this->assertEquals($description, $definition->getDescription());
    }

    public function testGetters(): void
    {
        $name = 'calculate';
        $description = 'Performs mathematical calculations';
        $definition = new FunctionDefinition($name, $description);

        $this->assertEquals($name, $definition->getName());
        $this->assertEquals($description, $definition->getDescription());
    }
}