<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\OpenAiException;
use OpenAIBundle\Exception\OpenAiGenericException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAiException::class)]
final class OpenAiExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return OpenAiException::class;
    }

    public function testOpenAiExceptionIsInstanceOfRuntimeException(): void
    {
        $exception = new OpenAiGenericException('Test message');

        $this->assertInstanceOf(\RuntimeException::class, $exception);
        $this->assertInstanceOf(OpenAiException::class, $exception);
    }

    public function testOpenAiExceptionPreservesMessage(): void
    {
        $message = 'Test error message';
        $exception = new OpenAiGenericException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testOpenAiExceptionPreservesCode(): void
    {
        $code = 123;
        $exception = new OpenAiGenericException('Test message', $code);

        $this->assertEquals($code, $exception->getCode());
    }

    public function testOpenAiExceptionPreservesPreviousException(): void
    {
        $previous = new \Exception('Previous exception');
        $exception = new OpenAiGenericException('Test message', 0, $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testApiRequestFailedCreatesExceptionWithFormattedMessage(): void
    {
        $message = 'Connection timeout';
        $exception = OpenAiGenericException::apiRequestFailed($message);

        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertEquals('OpenAi API请求失败: Connection timeout', $exception->getMessage());
    }

    public function testApiRequestFailedWithEmptyMessage(): void
    {
        $exception = OpenAiGenericException::apiRequestFailed('');

        $this->assertEquals('OpenAi API请求失败: ', $exception->getMessage());
    }

    public function testApiRequestFailedWithSpecialCharacters(): void
    {
        $message = 'Error with "quotes" and <tags>';
        $exception = OpenAiGenericException::apiRequestFailed($message);

        $this->assertEquals('OpenAi API请求失败: Error with "quotes" and <tags>', $exception->getMessage());
    }

    public function testInvalidConfigurationCreatesExceptionWithFormattedMessage(): void
    {
        $message = 'Missing API key';
        $exception = OpenAiGenericException::invalidConfiguration($message);

        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertEquals('OpenAi配置无效: Missing API key', $exception->getMessage());
    }

    public function testInvalidConfigurationWithEmptyMessage(): void
    {
        $exception = OpenAiGenericException::invalidConfiguration('');

        $this->assertEquals('OpenAi配置无效: ', $exception->getMessage());
    }

    public function testInvalidConfigurationWithComplexMessage(): void
    {
        $message = 'API key format is invalid: should start with "sk-"';
        $exception = OpenAiGenericException::invalidConfiguration($message);

        $this->assertEquals('OpenAi配置无效: API key format is invalid: should start with "sk-"', $exception->getMessage());
    }

    public function testOpenAiExceptionCanBeThrown(): void
    {
        $this->expectException(OpenAiException::class);
        $this->expectExceptionMessage('Test exception');

        throw new OpenAiGenericException('Test exception');
    }

    public function testOpenAiExceptionCanBeCaught(): void
    {
        $caught = false;

        try {
            throw new OpenAiGenericException('Test exception');
        } catch (OpenAiException $e) {
            $caught = true;
            $this->assertEquals('Test exception', $e->getMessage());
        }

        $this->assertTrue($caught, 'Exception should have been caught');
    }

    public function testApiRequestFailedCanBeThrown(): void
    {
        $this->expectException(OpenAiException::class);
        $this->expectExceptionMessage('OpenAi API请求失败: Network error');

        throw OpenAiGenericException::apiRequestFailed('Network error');
    }

    public function testInvalidConfigurationCanBeThrown(): void
    {
        $this->expectException(OpenAiException::class);
        $this->expectExceptionMessage('OpenAi配置无效: Invalid API endpoint');

        throw OpenAiGenericException::invalidConfiguration('Invalid API endpoint');
    }

    public function testOpenAiExceptionWithMultilineMessage(): void
    {
        $message = "Line 1\nLine 2\nLine 3";
        $exception = new OpenAiGenericException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testOpenAiExceptionWithUnicodeMessage(): void
    {
        $message = '错误信息包含中文字符和 emoji 🚫';
        $exception = new OpenAiGenericException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testStaticFactoryMethodsReturnSameClassInstance(): void
    {
        $exception1 = OpenAiGenericException::apiRequestFailed('test');
        $exception2 = OpenAiGenericException::invalidConfiguration('test');

        $this->assertInstanceOf(OpenAiException::class, $exception1);
        $this->assertInstanceOf(OpenAiException::class, $exception2);
        $this->assertInstanceOf(get_class($exception1), $exception2);
    }
}
