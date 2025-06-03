<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\OpenAiException;
use PHPUnit\Framework\TestCase;

class OpenAiExceptionTest extends TestCase
{
    public function test_openAiException_isInstanceOfRuntimeException(): void
    {
        $exception = new OpenAiException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function test_openAiException_preservesMessage(): void
    {
        $message = 'Test error message';
        $exception = new OpenAiException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_openAiException_preservesCode(): void
    {
        $code = 123;
        $exception = new OpenAiException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function test_openAiException_preservesPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new OpenAiException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function test_apiRequestFailed_createsExceptionWithFormattedMessage(): void
    {
        $message = 'Connection timeout';
        $exception = OpenAiException::apiRequestFailed($message);

        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertEquals('OpenAi API请求失败: Connection timeout', $exception->getMessage());
    }

    public function test_apiRequestFailed_withEmptyMessage(): void
    {
        $exception = OpenAiException::apiRequestFailed('');

        $this->assertEquals('OpenAi API请求失败: ', $exception->getMessage());
    }

    public function test_apiRequestFailed_withSpecialCharacters(): void
    {
        $message = 'Error with "quotes" and <tags>';
        $exception = OpenAiException::apiRequestFailed($message);

        $this->assertEquals('OpenAi API请求失败: Error with "quotes" and <tags>', $exception->getMessage());
    }

    public function test_invalidConfiguration_createsExceptionWithFormattedMessage(): void
    {
        $message = 'Missing API key';
        $exception = OpenAiException::invalidConfiguration($message);

        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertEquals('OpenAi配置无效: Missing API key', $exception->getMessage());
    }

    public function test_invalidConfiguration_withEmptyMessage(): void
    {
        $exception = OpenAiException::invalidConfiguration('');

        $this->assertEquals('OpenAi配置无效: ', $exception->getMessage());
    }

    public function test_invalidConfiguration_withComplexMessage(): void
    {
        $message = 'API key format is invalid: should start with "sk-"';
        $exception = OpenAiException::invalidConfiguration($message);

        $this->assertEquals('OpenAi配置无效: API key format is invalid: should start with "sk-"', $exception->getMessage());
    }

    public function test_openAiException_canBeThrown(): void
    {
        $this->expectException(OpenAiException::class);
        $this->expectExceptionMessage('Test exception');

        throw new OpenAiException('Test exception');
    }

    public function test_openAiException_canBeCaught(): void
    {
        $caught = false;

        try {
            throw new OpenAiException('Test exception');
        } catch (OpenAiException $e) {
            $caught = true;
            $this->assertEquals('Test exception', $e->getMessage());
        }

        $this->assertTrue($caught, 'Exception should have been caught');
    }

    public function test_apiRequestFailed_canBeThrown(): void
    {
        $this->expectException(OpenAiException::class);
        $this->expectExceptionMessage('OpenAi API请求失败: Network error');

        throw OpenAiException::apiRequestFailed('Network error');
    }

    public function test_invalidConfiguration_canBeThrown(): void
    {
        $this->expectException(OpenAiException::class);
        $this->expectExceptionMessage('OpenAi配置无效: Invalid API endpoint');

        throw OpenAiException::invalidConfiguration('Invalid API endpoint');
    }

    public function test_openAiException_withMultilineMessage(): void
    {
        $message = "Line 1\nLine 2\nLine 3";
        $exception = new OpenAiException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_openAiException_withUnicodeMessage(): void
    {
        $message = '错误信息包含中文字符和 emoji 🚫';
        $exception = new OpenAiException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function test_staticFactoryMethods_returnSameClassInstance(): void
    {
        $exception1 = OpenAiException::apiRequestFailed('test');
        $exception2 = OpenAiException::invalidConfiguration('test');

        $this->assertInstanceOf(OpenAiException::class, $exception1);
        $this->assertInstanceOf(OpenAiException::class, $exception2);
        $this->assertEquals(get_class($exception1), get_class($exception2));
    }
} 