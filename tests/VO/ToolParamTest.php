<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\Enum\ToolType;
use OpenAIBundle\VO\FunctionDefinition;
use OpenAIBundle\VO\ToolParam;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversClass(ToolParam::class)]
final class ToolParamTest extends TestCase
{
    public function testCreateToolParam(): void
    {
        $type = ToolType::function;
        $function = new FunctionDefinition('test_function', 'Test description');

        $toolParam = new ToolParam($type, $function);

        $this->assertInstanceOf(ToolParam::class, $toolParam);
        $this->assertEquals($type, $toolParam->getType());
        $this->assertEquals($function, $toolParam->getFunction());
    }

    public function testGetters(): void
    {
        $type = ToolType::function;
        $function = new FunctionDefinition('calculate', 'Calculation function');
        $toolParam = new ToolParam($type, $function);

        $this->assertEquals($type, $toolParam->getType());
        $this->assertEquals($function, $toolParam->getFunction());
        $this->assertEquals('calculate', $toolParam->getFunction()->getName());
    }
}
