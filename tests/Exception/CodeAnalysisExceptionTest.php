<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\CodeAnalysisException;
use PHPUnit\Framework\TestCase;

class CodeAnalysisExceptionTest extends TestCase
{
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