<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\OpenAiException;
use OpenAIBundle\Exception\OpenAiGenericException;
use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAiGenericException::class)]
final class OpenAiGenericExceptionTest extends AbstractExceptionTestCase
{
    protected function getExceptionClass(): string
    {
        return OpenAiGenericException::class;
    }

    public function testExceptionExtendsOpenAiException(): void
    {
        $exception = new OpenAiGenericException('Test message');

        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testApiRequestFailedCreatesCorrectException(): void
    {
        $message = 'Network timeout';
        $exception = OpenAiGenericException::apiRequestFailed($message);

        $this->assertInstanceOf(OpenAiGenericException::class, $exception);
        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertEquals('OpenAi API请求失败: Network timeout', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testInvalidConfigurationCreatesCorrectException(): void
    {
        $message = 'API key missing';
        $exception = OpenAiGenericException::invalidConfiguration($message);

        $this->assertInstanceOf(OpenAiGenericException::class, $exception);
        $this->assertInstanceOf(OpenAiException::class, $exception);
        $this->assertEquals('OpenAi配置无效: API key missing', $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testApiRequestFailedWithEmptyMessage(): void
    {
        $exception = OpenAiGenericException::apiRequestFailed('');

        $this->assertEquals('OpenAi API请求失败: ', $exception->getMessage());
    }

    public function testInvalidConfigurationWithEmptyMessage(): void
    {
        $exception = OpenAiGenericException::invalidConfiguration('');

        $this->assertEquals('OpenAi配置无效: ', $exception->getMessage());
    }

    public function testApiRequestFailedWithSpecialCharacters(): void
    {
        $message = 'Error with "quotes" and <tags>';
        $exception = OpenAiGenericException::apiRequestFailed($message);

        $this->assertEquals('OpenAi API请求失败: Error with "quotes" and <tags>', $exception->getMessage());
    }

    public function testInvalidConfigurationWithSpecialCharacters(): void
    {
        $message = 'Invalid URL format: <http://invalid>';
        $exception = OpenAiGenericException::invalidConfiguration($message);

        $this->assertEquals('OpenAi配置无效: Invalid URL format: <http://invalid>', $exception->getMessage());
    }

    public function testConstructorWithMessage(): void
    {
        $message = 'Custom error message';
        $exception = new OpenAiGenericException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessageAndCode(): void
    {
        $message = 'Custom error message';
        $code = 500;
        $exception = new OpenAiGenericException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
    }

    public function testConstructorWithMessageCodeAndPrevious(): void
    {
        $previous = new \Exception('Previous exception');
        $message = 'Custom error message';
        $code = 500;
        $exception = new OpenAiGenericException($message, $code, $previous);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testApiRequestFailedWithUnicodeMessage(): void
    {
        $message = '网络请求失败，请检查连接 🌐';
        $exception = OpenAiGenericException::apiRequestFailed($message);

        $this->assertEquals('OpenAi API请求失败: 网络请求失败，请检查连接 🌐', $exception->getMessage());
    }

    public function testInvalidConfigurationWithUnicodeMessage(): void
    {
        $message = 'API密钥格式错误 ❌';
        $exception = OpenAiGenericException::invalidConfiguration($message);

        $this->assertEquals('OpenAi配置无效: API密钥格式错误 ❌', $exception->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(OpenAiGenericException::class);
        $this->expectExceptionMessage('Test exception');

        throw new OpenAiGenericException('Test exception');
    }

    public function testExceptionCanBeCaught(): void
    {
        $caught = false;

        try {
            throw new OpenAiGenericException('Test exception');
        } catch (OpenAiGenericException $e) {
            $caught = true;
            $this->assertEquals('Test exception', $e->getMessage());
        }

        $this->assertTrue($caught, 'Exception should have been caught');
    }

    public function testExceptionCanBeCaughtAsOpenAiException(): void
    {
        $caught = false;

        try {
            throw new OpenAiGenericException('Test exception');
        } catch (OpenAiException $e) {
            $caught = true;
            $this->assertInstanceOf(OpenAiGenericException::class, $e);
            $this->assertEquals('Test exception', $e->getMessage());
        }

        $this->assertTrue($caught, 'Exception should have been caught as OpenAiException');
    }

    public function testFactoryMethodsReturnSameClass(): void
    {
        $exception1 = OpenAiGenericException::apiRequestFailed('test1');
        $exception2 = OpenAiGenericException::invalidConfiguration('test2');

        $this->assertInstanceOf(OpenAiGenericException::class, $exception1);
        $this->assertInstanceOf(OpenAiGenericException::class, $exception2);
        $this->assertEquals(get_class($exception1), get_class($exception2));
    }

    public function testMultilineMessage(): void
    {
        $message = "Line 1\nLine 2\nLine 3";
        $exception = OpenAiGenericException::apiRequestFailed($message);

        $this->assertEquals("OpenAi API请求失败: Line 1\nLine 2\nLine 3", $exception->getMessage());
        $this->assertStringContainsString("\n", $exception->getMessage());
    }

    public function testVeryLongMessage(): void
    {
        $message = str_repeat('Very long error message. ', 100);
        $exception = OpenAiGenericException::invalidConfiguration($message);

        $this->assertEquals('OpenAi配置无效: ' . $message, $exception->getMessage());
        $this->assertGreaterThan(2000, strlen($exception->getMessage()));
    }

    public function testMessageFormattingPreservesOriginalMessage(): void
    {
        $originalMessage = 'Original message with %s placeholders and %d numbers';
        $exception = OpenAiGenericException::apiRequestFailed($originalMessage);

        // 确保sprintf格式化字符不会被意外处理
        $this->assertEquals('OpenAi API请求失败: ' . $originalMessage, $exception->getMessage());
        $this->assertStringContainsString('%s', $exception->getMessage());
        $this->assertStringContainsString('%d', $exception->getMessage());
    }

    public function testFactoryMethodsWithNullMessage(): void
    {
        // PHP的类型系统会阻止传递null，但我们测试空字符串的行为
        $exception1 = OpenAiGenericException::apiRequestFailed('');
        $exception2 = OpenAiGenericException::invalidConfiguration('');

        $this->assertEquals('OpenAi API请求失败: ', $exception1->getMessage());
        $this->assertEquals('OpenAi配置无效: ', $exception2->getMessage());
    }
}
