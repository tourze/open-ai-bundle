<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\TaskStatus;
use PHPUnit\Framework\TestCase;

class TaskStatusTest extends TestCase
{
    public function testTaskStatus_hasExpectedValues(): void
    {
        $this->assertEquals('pending', TaskStatus::PENDING->value);
        $this->assertEquals('running', TaskStatus::RUNNING->value);
        $this->assertEquals('completed', TaskStatus::COMPLETED->value);
        $this->assertEquals('failed', TaskStatus::FAILED->value);
    }

    public function testGetLabel_returnsCorrectLabels(): void
    {
        $this->assertEquals('待处理', TaskStatus::PENDING->getLabel());
        $this->assertEquals('执行中', TaskStatus::RUNNING->getLabel());
        $this->assertEquals('已完成', TaskStatus::COMPLETED->getLabel());
        $this->assertEquals('失败', TaskStatus::FAILED->getLabel());
    }

    public function testAllCases_returnsExpectedCases(): void
    {
        $cases = TaskStatus::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(TaskStatus::PENDING, $cases);
        $this->assertContains(TaskStatus::RUNNING, $cases);
        $this->assertContains(TaskStatus::COMPLETED, $cases);
        $this->assertContains(TaskStatus::FAILED, $cases);
    }

    public function testTaskStatus_isInstanceOfBackedEnum(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, TaskStatus::PENDING);
        $this->assertInstanceOf(\BackedEnum::class, TaskStatus::RUNNING);
        $this->assertInstanceOf(\BackedEnum::class, TaskStatus::COMPLETED);
        $this->assertInstanceOf(\BackedEnum::class, TaskStatus::FAILED);
    }

    public function testTaskStatus_implementsLabelable(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, TaskStatus::PENDING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, TaskStatus::RUNNING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, TaskStatus::COMPLETED);
        $this->assertInstanceOf(\Tourze\EnumExtra\Labelable::class, TaskStatus::FAILED);
    }

    public function testTaskStatus_implementsItemable(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, TaskStatus::PENDING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, TaskStatus::RUNNING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, TaskStatus::COMPLETED);
        $this->assertInstanceOf(\Tourze\EnumExtra\Itemable::class, TaskStatus::FAILED);
    }

    public function testTaskStatus_implementsSelectable(): void
    {
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, TaskStatus::PENDING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, TaskStatus::RUNNING);
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, TaskStatus::COMPLETED);
        $this->assertInstanceOf(\Tourze\EnumExtra\Selectable::class, TaskStatus::FAILED);
    }

    public function testTryFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(TaskStatus::PENDING, TaskStatus::tryFrom('pending'));
        $this->assertEquals(TaskStatus::RUNNING, TaskStatus::tryFrom('running'));
        $this->assertEquals(TaskStatus::COMPLETED, TaskStatus::tryFrom('completed'));
        $this->assertEquals(TaskStatus::FAILED, TaskStatus::tryFrom('failed'));
    }

    public function testTryFrom_returnsNullForInvalidValue(): void
    {
        $this->assertNull(TaskStatus::tryFrom('started'));
        $this->assertNull(TaskStatus::tryFrom('finished'));
        $this->assertNull(TaskStatus::tryFrom('error'));
        $this->assertNull(TaskStatus::tryFrom(''));
        $this->assertNull(TaskStatus::tryFrom('invalid'));
    }

    public function testFrom_returnsCorrectEnumForValidValue(): void
    {
        $this->assertEquals(TaskStatus::PENDING, TaskStatus::from('pending'));
        $this->assertEquals(TaskStatus::RUNNING, TaskStatus::from('running'));
        $this->assertEquals(TaskStatus::COMPLETED, TaskStatus::from('completed'));
        $this->assertEquals(TaskStatus::FAILED, TaskStatus::from('failed'));
    }

    public function testFrom_throwsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        TaskStatus::from('invalid_status');
    }

    public function testValueConsistency(): void
    {
        // 确保值与案例名称相匹配（小写）
        $this->assertEquals('pending', TaskStatus::PENDING->value);
        $this->assertEquals('running', TaskStatus::RUNNING->value);
        $this->assertEquals('completed', TaskStatus::COMPLETED->value);
        $this->assertEquals('failed', TaskStatus::FAILED->value);
    }

    public function testLabelConsistency(): void
    {
        $expectedLabels = [
            TaskStatus::PENDING->value => '待处理',
            TaskStatus::RUNNING->value => '执行中',
            TaskStatus::COMPLETED->value => '已完成',
            TaskStatus::FAILED->value => '失败'
        ];

        foreach (TaskStatus::cases() as $status) {
            $this->assertEquals($expectedLabels[$status->value], $status->getLabel());
        }
    }

    public function testEnumInArrayContext(): void
    {
        $statuses = [
            TaskStatus::PENDING,
            TaskStatus::RUNNING,
            TaskStatus::COMPLETED,
            TaskStatus::FAILED
        ];

        $this->assertCount(4, $statuses);
        $this->assertContains(TaskStatus::PENDING, $statuses);
        $this->assertContains(TaskStatus::RUNNING, $statuses);
        $this->assertContains(TaskStatus::COMPLETED, $statuses);
        $this->assertContains(TaskStatus::FAILED, $statuses);
    }

    public function testEnumComparison(): void
    {
        $this->assertTrue(TaskStatus::PENDING === TaskStatus::PENDING);
        $this->assertFalse(TaskStatus::PENDING === TaskStatus::RUNNING);
        $this->assertFalse(TaskStatus::COMPLETED === TaskStatus::FAILED);
    }

    public function testTaskStatusWorkflow(): void
    {
        // 测试任务状态的正常流转
        $workflow = [
            TaskStatus::PENDING,    // 开始状态
            TaskStatus::RUNNING,    // 执行中
            TaskStatus::COMPLETED   // 成功完成
        ];

        $this->assertCount(3, $workflow);
        
        // 验证每个状态都有正确的值和标签
        foreach ($workflow as $status) {
            $this->assertInstanceOf(TaskStatus::class, $status);
            $this->assertIsString($status->value);
            $this->assertIsString($status->getLabel());
            $this->assertNotEmpty($status->value);
            $this->assertNotEmpty($status->getLabel());
        }
    }

    public function testFailureScenario(): void
    {
        // 测试失败场景
        $failureFlow = [
            TaskStatus::PENDING,
            TaskStatus::RUNNING,
            TaskStatus::FAILED
        ];

        $this->assertCount(3, $failureFlow);
        $this->assertEquals('失败', TaskStatus::FAILED->getLabel());
        $this->assertEquals('failed', TaskStatus::FAILED->value);
    }

    public function testStatusValidation(): void
    {
        // 测试状态验证场景
        $validStatuses = ['pending', 'running', 'completed', 'failed'];
        $invalidStatuses = ['created', 'started', 'finished', 'error', ''];

        foreach ($validStatuses as $status) {
            $this->assertNotNull(TaskStatus::tryFrom($status));
        }

        foreach ($invalidStatuses as $status) {
            $this->assertNull(TaskStatus::tryFrom($status));
        }
    }

    public function testAllStatusesHaveLabels(): void
    {
        // 确保所有状态都有标签
        foreach (TaskStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
    }

    public function testStatusProgression(): void
    {
        // 测试状态进展逻辑
        $this->assertTrue(TaskStatus::PENDING->value !== TaskStatus::RUNNING->value);
        $this->assertTrue(TaskStatus::RUNNING->value !== TaskStatus::COMPLETED->value);
        $this->assertTrue(TaskStatus::COMPLETED->value !== TaskStatus::FAILED->value);
    }
} 