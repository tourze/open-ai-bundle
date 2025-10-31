<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\CodeAnalysisException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(CodeAnalysisException::class)]
final class CodeAnalysisExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return CodeAnalysisException::class;
    }

    public function testClassNotFoundExceptionMessage(): void
    {
        $filepath = '/path/to/file.php';
        $exception = CodeAnalysisException::classNotFound($filepath);

        $this->assertInstanceOf(CodeAnalysisException::class, $exception);
        $this->assertStringContainsString($filepath, $exception->getMessage());
        $this->assertStringContainsString('无法从文件', $exception->getMessage());
    }

    public function testExceptionExtendsRuntimeException(): void
    {
        $exception = CodeAnalysisException::classNotFound('test.php');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
