<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\FunctionParamType;
use PHPUnit\Framework\TestCase;

class FunctionParamTypeTest extends TestCase
{
    public function testFunctionParamType_hasExpectedValues(): void
    {
        $this->assertEquals('string', FunctionParamType::string->value);
        $this->assertEquals('integer', FunctionParamType::integer->value);
        $this->assertEquals('boolean', FunctionParamType::boolean->value);
    }

    public function testAllCases_returnsExpectedCases(): void
    {
        $cases = FunctionParamType::cases();

        $this->assertCount(3, $cases);
        $this->assertContains(FunctionParamType::string, $cases);
        $this->assertContains(FunctionParamType::integer, $cases);
        $this->assertContains(FunctionParamType::boolean, $cases);
    }

    public function testFunctionParamType_isInstanceOfBackedEnum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, FunctionParamType::string);
        $this->assertInstanceOf(\BackedEnum::class, FunctionParamType::integer);
        $this->assertInstanceOf(\BackedEnum::class, FunctionParamType::boolean);
    }

    public function testTryFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(FunctionParamType::string, FunctionParamType::tryFrom('string'));
        $this->assertEquals(FunctionParamType::integer, FunctionParamType::tryFrom('integer'));
        $this->assertEquals(FunctionParamType::boolean, FunctionParamType::tryFrom('boolean'));
    }

    public function testTryFrom_returnsNullForInvalidValue(): void
    {
        $this->assertNull(FunctionParamType::tryFrom('number'));
        $this->assertNull(FunctionParamType::tryFrom('array'));
        $this->assertNull(FunctionParamType::tryFrom('object'));
        $this->assertNull(FunctionParamType::tryFrom(''));
        $this->assertNull(FunctionParamType::tryFrom('null'));
    }

    public function testFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(FunctionParamType::string, FunctionParamType::from('string'));
        $this->assertEquals(FunctionParamType::integer, FunctionParamType::from('integer'));
        $this->assertEquals(FunctionParamType::boolean, FunctionParamType::from('boolean'));
    }

    public function testFrom_throwsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        FunctionParamType::from('invalid');
    }

    public function testValueConsistency(): void
    {
        // 确保值与案例名称保持一致
        $this->assertEquals('string', FunctionParamType::string->value);
        $this->assertEquals('integer', FunctionParamType::integer->value);
        $this->assertEquals('boolean', FunctionParamType::boolean->value);
    }

    public function testEnumInArrayContext(): void
    {
        $types = [
            FunctionParamType::string,
            FunctionParamType::integer,
            FunctionParamType::boolean
        ];

        $this->assertCount(3, $types);
        $this->assertContains(FunctionParamType::string, $types);
        $this->assertContains(FunctionParamType::integer, $types);
        $this->assertContains(FunctionParamType::boolean, $types);
    }

    public function testEnumComparison(): void
    {
        $this->assertTrue(FunctionParamType::string === FunctionParamType::string);
        $this->assertFalse(FunctionParamType::string === FunctionParamType::integer);
        $this->assertFalse(FunctionParamType::integer === FunctionParamType::boolean);
    }

    public function testEnumSerialization(): void
    {
        $this->assertEquals('string', FunctionParamType::string->value);
        $this->assertEquals('integer', FunctionParamType::integer->value);
        $this->assertEquals('boolean', FunctionParamType::boolean->value);
    }
} 