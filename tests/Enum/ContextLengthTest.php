<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\ContextLength;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(ContextLength::class)]
final class ContextLengthTest extends AbstractEnumTestCase
{
    public function testContextLengthImplementsItemable(): void
    {
        $this->assertInstanceOf(Itemable::class, ContextLength::K_4);
    }

    public function testContextLengthImplementsSelectable(): void
    {
        $this->assertInstanceOf(Selectable::class, ContextLength::K_4);
    }

    public function testToArray(): void
    {
        $result = ContextLength::K_4->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals(4096, $result['value']);
        $this->assertEquals('4K', $result['label']);
    }
}
