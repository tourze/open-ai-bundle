<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\ModelException;
use PHPUnit\Framework\TestCase;

class ModelExceptionTest extends TestCase
{
    public function testException_extendsCorrectBaseClass(): void
    {
        $exception = new ModelException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testException_withMessage(): void
    {
        $message = 'Model error occurred';
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testException_withMessageAndCode(): void
    {
        $message = 'Invalid model';
        $code = 2001;
        $exception = new ModelException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testException_withMessageCodeAndPrevious(): void
    {
        $previousException = new \InvalidArgumentException('Previous error');
        $message = 'Model validation failed';
        $code = 3001;
        
        $exception = new ModelException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testException_defaultCode(): void
    {
        $exception = new ModelException('Test message');
        
        $this->assertEquals(0, $exception->getCode());
    }

    public function testException_emptyMessage(): void
    {
        $exception = new ModelException('');
        
        $this->assertEquals('', $exception->getMessage());
    }

    public function testException_canBeThrownAndCaught(): void
    {
        $message = 'Test model exception';
        
        try {
            throw new ModelException($message);
        } catch (ModelException $e) {
            $this->assertEquals($message, $e->getMessage());
            $this->assertInstanceOf(ModelException::class, $e);
        }
    }

    public function testException_canBeCaughtAsRuntimeException(): void
    {
        $message = 'Runtime model error';
        
        try {
            throw new ModelException($message);
        } catch (\RuntimeException $e) {
            $this->assertEquals($message, $e->getMessage());
            $this->assertInstanceOf(ModelException::class, $e);
        }
    }

    public function testException_canBeCaughtAsGeneralException(): void
    {
        $message = 'General model error';
        
        try {
            throw new ModelException($message);
        } catch (\Exception $e) {
            $this->assertEquals($message, $e->getMessage());
            $this->assertInstanceOf(ModelException::class, $e);
        }
    }

    public function testException_withModelSpecificMessage(): void
    {
        $message = 'Model "gpt-4" is not available in your region';
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('gpt-4', $exception->getMessage());
        $this->assertStringContainsString('not available', $exception->getMessage());
    }

    public function testException_withTokenLimitMessage(): void
    {
        $message = 'Token limit exceeded: 4096 tokens requested, but model supports maximum 2048 tokens';
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('Token limit', $exception->getMessage());
        $this->assertStringContainsString('4096', $exception->getMessage());
    }

    public function testException_withUnicodeMessage(): void
    {
        $message = '模型错误：不支持的模型类型 🚫 请选择有效模型 ⚙️';
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('模型错误', $exception->getMessage());
        $this->assertStringContainsString('不支持的模型类型', $exception->getMessage());
    }

    public function testException_withJsonMessage(): void
    {
        $errorData = [
            'error' => 'model_not_found',
            'model' => 'gpt-5',
            'available_models' => ['gpt-3.5-turbo', 'gpt-4']
        ];
        $message = json_encode($errorData);
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertJson($exception->getMessage());
        
        $decoded = json_decode($exception->getMessage(), true);
        $this->assertEquals('model_not_found', $decoded['error']);
        $this->assertEquals('gpt-5', $decoded['model']);
    }

    public function testException_stackTraceContainsCorrectFile(): void
    {
        try {
            throw new ModelException('Stack trace test');
        } catch (ModelException $e) {
            $trace = $e->getTrace();
            $this->assertNotEmpty($trace);
            
            // 检查栈追踪包含测试文件相关信息
            $traceAsString = $e->getTraceAsString();
            $this->assertIsString($traceAsString);
            $this->assertStringContainsString('ModelExceptionTest', $traceAsString);
        }
    }

    public function testException_toStringFormat(): void
    {
        $message = 'String representation test';
        $exception = new ModelException($message);

        $stringRepresentation = (string) $exception;
        
        $this->assertStringContainsString('ModelException', $stringRepresentation);
        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }

    public function testException_chaining(): void
    {
        $rootCause = new \InvalidArgumentException('Root cause');
        $intermediate = new \RuntimeException('Intermediate', 0, $rootCause);
        $modelException = new ModelException('Model processing failed', 0, $intermediate);

        $this->assertSame($intermediate, $modelException->getPrevious());
        $this->assertSame($rootCause, $modelException->getPrevious()->getPrevious());
    }

    public function testException_negativeCode(): void
    {
        $message = 'Negative code test';
        $code = -500;
        $exception = new ModelException($message, $code);

        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testException_largeCode(): void
    {
        $message = 'Large code test';
        $code = PHP_INT_MAX;
        $exception = new ModelException($message, $code);

        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testException_multilineMessage(): void
    {
        $message = "Model error:\nLine 1: Invalid model name\nLine 2: Unsupported version\nLine 3: Rate limit exceeded";
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString("\n", $exception->getMessage());
        $this->assertStringContainsString('Invalid model name', $exception->getMessage());
    }

    public function testException_withApiKeyRelatedMessage(): void
    {
        $message = 'Model access denied: API key does not have permission to use gpt-4';
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('API key', $exception->getMessage());
        $this->assertStringContainsString('permission', $exception->getMessage());
    }

    public function testException_withRateLimitMessage(): void
    {
        $message = 'Rate limit reached for model gpt-4: 3 requests per minute exceeded';
        $exception = new ModelException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('Rate limit', $exception->getMessage());
        $this->assertStringContainsString('3 requests', $exception->getMessage());
    }
} 