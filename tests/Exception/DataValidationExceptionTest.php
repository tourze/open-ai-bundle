<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\DataValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(DataValidationException::class)]
final class DataValidationExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return DataValidationException::class;
    }

    public function testMissingRequiredFields(): void
    {
        $exception = DataValidationException::missingRequiredFields('Test message');

        $this->assertInstanceOf(DataValidationException::class, $exception);
        $this->assertEquals('数据验证失败: Test message', $exception->getMessage());
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}
