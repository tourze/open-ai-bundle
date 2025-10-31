<?php

namespace OpenAIBundle\Tests\Enum;

use OpenAIBundle\Enum\TaskStatus;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\Selectable;
use Tourze\PHPUnitEnum\AbstractEnumTestCase;

/**
 * @internal
 */
#[CoversClass(TaskStatus::class)]
final class TaskStatusTest extends AbstractEnumTestCase
{
    public function testTaskStatusImplementsItemable(): void
    {
        $this->assertInstanceOf(Itemable::class, TaskStatus::PENDING);
    }

    public function testTaskStatusImplementsSelectable(): void
    {
        $this->assertInstanceOf(Selectable::class, TaskStatus::PENDING);
    }

    public function testTaskStatusWorkflow(): void
    {
        $workflow = [
            TaskStatus::PENDING,
            TaskStatus::RUNNING,
            TaskStatus::COMPLETED,
        ];

        $this->assertCount(3, $workflow);

        foreach ($workflow as $status) {
            $this->assertInstanceOf(TaskStatus::class, $status);
            $this->assertNotEmpty($status->value);
            $this->assertNotEmpty($status->getLabel());
        }
    }

    public function testToArray(): void
    {
        $result = TaskStatus::PENDING->toArray();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('value', $result);
        $this->assertArrayHasKey('label', $result);
        $this->assertEquals('pending', $result['value']);
        $this->assertEquals('待处理', $result['label']);
    }
}
