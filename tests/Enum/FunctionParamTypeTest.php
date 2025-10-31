<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(FunctionParamType::class)]
final class FunctionParamTypeTest extends AbstractEnumTestCase
{
    public function testParameterTypeMapping(): void
    {
        $this->assertEquals('string', FunctionParamType::string->value);
        $this->assertEquals('integer', FunctionParamType::integer->value);
        $this->assertEquals('boolean', FunctionParamType::boolean->value);
    }

    public function testToArray(): void
    {
        $result = FunctionParamType::string->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('string', $result['value']);
        $this->assertEquals('字符串', $result['label']);
    }
}
