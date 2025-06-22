<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\ToolType;
use PHPUnit\Framework\TestCase;

class ToolTypeTest extends TestCase
{
    public function testFunction_hasCorrectValue(): void
    {
        $this->assertEquals('function', ToolType::function->value);
    }

    public function testFunction_constantExists(): void
    {
        $this->assertTrue(defined('OpenAIBundle\Enum\ToolType::function'));
    }

    public function testAllCases_returnsCompleteList(): void
    {
        $cases = ToolType::cases();
        
        $this->assertCount(1, $cases);
        $this->assertContains(ToolType::function, $cases);
    }

    public function testFrom_withValidValue(): void
    {
        $toolType = ToolType::from('function');
        
        $this->assertEquals(ToolType::function, $toolType);
        $this->assertEquals('function', $toolType->value);
    }

    public function testFrom_withInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        
        ToolType::from('invalid_type');
    }

    public function testTryFrom_withValidValue(): void
    {
        $toolType = ToolType::tryFrom('function');
        
        $this->assertEquals(ToolType::function, $toolType);
    }

    public function testTryFrom_withInvalidValue(): void
    {
        $toolType = ToolType::tryFrom('invalid_type');
        
        $this->assertNull($toolType);
    }

    public function testTryFrom_withEmptyString(): void
    {
        $toolType = ToolType::tryFrom('');
        
        $this->assertNull($toolType);
    }

    public function testEnum_isInstanceOfUnitEnum(): void
    {
        $this->assertInstanceOf(\UnitEnum::class, ToolType::function);
    }

    public function testEnum_isInstanceOfBackedEnum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, ToolType::function);
    }

    public function testToolTypeValidation_withValidTypes(): void
    {
        $validTypes = ['function'];
        
        foreach ($validTypes as $type) {
            $toolType = ToolType::tryFrom($type);
            $this->assertNotNull($toolType, "ToolType '$type' 应该是有效的");
            $this->assertEquals($type, $toolType->value);
        }
    }

    public function testToolTypeValidation_withInvalidTypes(): void
    {
        $invalidTypes = ['tool', 'action', 'command', 'procedure', 'method'];
        
        foreach ($invalidTypes as $type) {
            $toolType = ToolType::tryFrom($type);
            $this->assertNull($toolType, "ToolType '$type' 应该是无效的");
        }
    }

    public function testStringComparison_worksCorrectly(): void
    {
        $toolType = ToolType::function;
        
        $this->assertTrue($toolType->value === 'function');
    }

    public function testEnumComparison_worksCorrectly(): void
    {
        $toolType1 = ToolType::function;
        $toolType2 = ToolType::from('function');
        
        $this->assertTrue($toolType1 === $toolType2);
        $this->assertEquals($toolType1, $toolType2);
    }

    public function testSerialization_preservesValue(): void
    {
        $original = ToolType::function;
        $serialized = serialize($original);
        $unserialized = unserialize($serialized);
        
        $this->assertEquals($original, $unserialized);
        $this->assertEquals($original->value, $unserialized->value);
    }

    public function testJsonSerialization_returnsValue(): void
    {
        $toolType = ToolType::function;
        
        $this->assertEquals('"function"', json_encode($toolType));
    }

    public function testCaseSensitivity_isStrict(): void
    {
        $this->assertNull(ToolType::tryFrom('Function'));
        $this->assertNull(ToolType::tryFrom('FUNCTION'));
        $this->assertNull(ToolType::tryFrom('Function'));
    }

    public function testGetName_returnsCorrectName(): void
    {
        $this->assertEquals('function', ToolType::function->name);
    }
} 