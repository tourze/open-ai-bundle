<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(RoleEnum::class)]
final class RoleEnumTest extends AbstractEnumTestCase
{
    public function testRoleHierarchy(): void
    {
        $conversationFlow = [
            RoleEnum::system,
            RoleEnum::user,
            RoleEnum::assistant,
            RoleEnum::tool,
        ];

        $this->assertCount(4, $conversationFlow);

        foreach ($conversationFlow as $role) {
            $this->assertInstanceOf(RoleEnum::class, $role);
            $this->assertNotEmpty($role->value);
        }
    }

    public function testRoleStringConversion(): void
    {
        $roleValues = [];
        foreach (RoleEnum::cases() as $role) {
            $roleValues[] = $role->value;
        }

        $this->assertEquals(['system', 'user', 'assistant', 'tool'], $roleValues);
    }

    public function testToArray(): void
    {
        $result = RoleEnum::system->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('system', $result['value']);
        $this->assertEquals('系统', $result['label']);
    }
}
