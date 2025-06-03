<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\ContextLength;
use PHPUnit\Framework\TestCase;

class ContextLengthTest extends TestCase
{
    public function test_contextLength_hasExpectedValues(): void
    {
        $this->assertEquals(4096, ContextLength::K_4->value);
        $this->assertEquals(8192, ContextLength::K_8->value);
        $this->assertEquals(16384, ContextLength::K_16->value);
        $this->assertEquals(32768, ContextLength::K_32->value);
        $this->assertEquals(65536, ContextLength::K_64->value);
        $this->assertEquals(131072, ContextLength::K_128->value);
    }

    public function test_getLabel_returnsCorrectLabels(): void
    {
        $this->assertEquals('4K', ContextLength::K_4->getLabel());
        $this->assertEquals('8K', ContextLength::K_8->getLabel());
        $this->assertEquals('16K', ContextLength::K_16->getLabel());
        $this->assertEquals('32K', ContextLength::K_32->getLabel());
        $this->assertEquals('64K', ContextLength::K_64->getLabel());
        $this->assertEquals('128K', ContextLength::K_128->getLabel());
    }

    public function test_allCases_returnsExpectedCases(): void
    {
        $cases = ContextLength::cases();

        $this->assertCount(6, $cases);
        $this->assertContains(ContextLength::K_4, $cases);
        $this->assertContains(ContextLength::K_8, $cases);
        $this->assertContains(ContextLength::K_16, $cases);
        $this->assertContains(ContextLength::K_32, $cases);
        $this->assertContains(ContextLength::K_64, $cases);
        $this->assertContains(ContextLength::K_128, $cases);
    }

    public function test_contextLength_canBeUsedInComparison(): void
    {
        $this->assertTrue(ContextLength::K_8->value > ContextLength::K_4->value);
        $this->assertTrue(ContextLength::K_16->value > ContextLength::K_8->value);
        $this->assertTrue(ContextLength::K_128->value > ContextLength::K_64->value);
    }

    public function test_contextLength_isInstanceOfBackedEnum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, ContextLength::K_4);
        $this->assertInstanceOf(\BackedEnum::class, ContextLength::K_128);
    }

    public function test_tryFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(ContextLength::K_4, ContextLength::tryFrom(4096));
        $this->assertEquals(ContextLength::K_8, ContextLength::tryFrom(8192));
        $this->assertEquals(ContextLength::K_128, ContextLength::tryFrom(131072));
    }

    public function test_tryFrom_returnsNullForInvalidValue(): void
    {
        $this->assertNull(ContextLength::tryFrom(1024));
        $this->assertNull(ContextLength::tryFrom(0));
        $this->assertNull(ContextLength::tryFrom(-1));
        $this->assertNull(ContextLength::tryFrom(999999));
    }

    public function test_from_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(ContextLength::K_16, ContextLength::from(16384));
        $this->assertEquals(ContextLength::K_32, ContextLength::from(32768));
    }

    public function test_from_throwsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ContextLength::from(2048);
    }

    public function test_contextLength_implementsLabelable(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, ContextLength::K_4);
    }

    public function test_contextLength_implementsItemable(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, ContextLength::K_4);
    }

    public function test_contextLength_implementsSelectable(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, ContextLength::K_4);
    }
} 