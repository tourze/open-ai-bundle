<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\TestCase;

class RoleEnumTest extends TestCase
{
    public function testRoleEnum_hasExpectedValues(): void
    {
        $this->assertEquals('system', RoleEnum::system->value);
        $this->assertEquals('user', RoleEnum::user->value);
        $this->assertEquals('assistant', RoleEnum::assistant->value);
        $this->assertEquals('tool', RoleEnum::tool->value);
    }

    public function testAllCases_returnsExpectedCases(): void
    {
        $cases = RoleEnum::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(RoleEnum::system, $cases);
        $this->assertContains(RoleEnum::user, $cases);
        $this->assertContains(RoleEnum::assistant, $cases);
        $this->assertContains(RoleEnum::tool, $cases);
    }

    public function testRoleEnum_isInstanceOfBackedEnum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, RoleEnum::system);
        $this->assertInstanceOf(\BackedEnum::class, RoleEnum::user);
        $this->assertInstanceOf(\BackedEnum::class, RoleEnum::assistant);
        $this->assertInstanceOf(\BackedEnum::class, RoleEnum::tool);
    }

    public function testTryFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(RoleEnum::system, RoleEnum::tryFrom('system'));
        $this->assertEquals(RoleEnum::user, RoleEnum::tryFrom('user'));
        $this->assertEquals(RoleEnum::assistant, RoleEnum::tryFrom('assistant'));
        $this->assertEquals(RoleEnum::tool, RoleEnum::tryFrom('tool'));
    }

    public function testTryFrom_returnsNullForInvalidValue(): void
    {
        $this->assertNull(RoleEnum::tryFrom('admin'));
        $this->assertNull(RoleEnum::tryFrom('bot'));
        $this->assertNull(RoleEnum::tryFrom('agent'));
        $this->assertNull(RoleEnum::tryFrom(''));
        $this->assertNull(RoleEnum::tryFrom('invalid'));
    }

    public function testFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(RoleEnum::system, RoleEnum::from('system'));
        $this->assertEquals(RoleEnum::user, RoleEnum::from('user'));
        $this->assertEquals(RoleEnum::assistant, RoleEnum::from('assistant'));
        $this->assertEquals(RoleEnum::tool, RoleEnum::from('tool'));
    }

    public function testFrom_throwsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        RoleEnum::from('invalid_role');
    }

    public function testValueConsistency(): void
    {
        // 确保值与案例名称保持一致
        $this->assertEquals('system', RoleEnum::system->value);
        $this->assertEquals('user', RoleEnum::user->value);
        $this->assertEquals('assistant', RoleEnum::assistant->value);
        $this->assertEquals('tool', RoleEnum::tool->value);
    }

    public function testEnumInArrayContext(): void
    {
        $roles = [
            RoleEnum::system,
            RoleEnum::user,
            RoleEnum::assistant,
            RoleEnum::tool
        ];

        $this->assertCount(4, $roles);
        $this->assertContains(RoleEnum::system, $roles);
        $this->assertContains(RoleEnum::user, $roles);
        $this->assertContains(RoleEnum::assistant, $roles);
        $this->assertContains(RoleEnum::tool, $roles);
    }

    public function testEnumComparison(): void
    {
        $this->assertTrue(RoleEnum::user === RoleEnum::user);
        $this->assertFalse(RoleEnum::user === RoleEnum::assistant);
        $this->assertFalse(RoleEnum::system === RoleEnum::tool);
    }

    public function testEnumSerialization(): void
    {
        $this->assertEquals('system', RoleEnum::system->value);
        $this->assertEquals('user', RoleEnum::user->value);
        $this->assertEquals('assistant', RoleEnum::assistant->value);
        $this->assertEquals('tool', RoleEnum::tool->value);
    }

    public function testRoleHierarchy(): void
    {
        // 测试角色在对话流程中的使用场景
        $conversationFlow = [
            RoleEnum::system,    // 系统提示
            RoleEnum::user,      // 用户提问
            RoleEnum::assistant, // AI回复
            RoleEnum::tool,      // 工具调用结果
        ];

        $this->assertCount(4, $conversationFlow);
        
        // 验证每个角色都有正确的值
        foreach ($conversationFlow as $role) {
            $this->assertInstanceOf(RoleEnum::class, $role);
            $this->assertIsString($role->value);
            $this->assertNotEmpty($role->value);
        }
    }

    public function testRoleStringConversion(): void
    {
        // 测试角色值可以正确转换为字符串
        $roleValues = [];
        foreach (RoleEnum::cases() as $role) {
            $roleValues[] = $role->value;
        }

        $this->assertEquals(['system', 'user', 'assistant', 'tool'], $roleValues);
    }

    public function testRoleValidation(): void
    {
        // 测试常见的角色验证场景
        $validRoles = ['system', 'user', 'assistant', 'tool'];
        $invalidRoles = ['admin', 'moderator', 'guest', ''];

        foreach ($validRoles as $role) {
            $this->assertNotNull(RoleEnum::tryFrom($role));
        }

        foreach ($invalidRoles as $role) {
            $this->assertNull(RoleEnum::tryFrom($role));
        }
    }
} 