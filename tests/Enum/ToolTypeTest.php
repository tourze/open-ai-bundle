<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\ToolType;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ToolType::class)]
final class ToolTypeTest extends AbstractEnumTestCase
{
    public function testJsonSerializationReturnsValue(): void
    {
        $toolType = ToolType::function;

        $this->assertEquals('"function"', json_encode($toolType));
    }

    public function testToArray(): void
    {
        $result = ToolType::function->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('function', $result['value']);
        $this->assertEquals('函数', $result['label']);
    }
}
