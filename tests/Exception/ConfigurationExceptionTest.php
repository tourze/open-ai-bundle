<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\ConfigurationException;
use PHPUnit\Framework\TestCase;

class ConfigurationExceptionTest extends TestCase
{
    public function testException_extendsCorrectBaseClass(): void
    {
        $exception = new ConfigurationException('Test message');
        
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testException_withMessage(): void
    {
        $message = 'Configuration error occurred';
        $exception = new ConfigurationException($message);

        $this->assertEquals($message, $exception->getMessage());
    }

    public function testException_withMessageAndCode(): void
    {
        $message = 'Invalid configuration';
        $code = 1001;
        $exception = new ConfigurationException($message, $code);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
    }

    public function testException_withMessageCodeAndPrevious(): void
    {
        $previousException = new \InvalidArgumentException('Previous error');
        $message = 'Configuration validation failed';
        $code = 2001;
        
        $exception = new ConfigurationException($message, $code, $previousException);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertEquals($code, $exception->getCode());
        $this->assertSame($previousException, $exception->getPrevious());
    }

    public function testException_defaultCode(): void
    {
        $exception = new ConfigurationException('Test message');
        
        $this->assertEquals(0, $exception->getCode());
    }

    public function testException_emptyMessage(): void
    {
        $exception = new ConfigurationException('');
        
        $this->assertEquals('', $exception->getMessage());
    }

    public function testException_canBeThrownAndCaught(): void
    {
        $message = 'Test configuration exception';
        
        try {
            throw new ConfigurationException($message);
        } catch (ConfigurationException $e) {
            $this->assertEquals($message, $e->getMessage());
            $this->assertInstanceOf(ConfigurationException::class, $e);
        }
    }

    public function testException_canBeCaughtAsRuntimeException(): void
    {
        $message = 'Runtime configuration error';
        
        try {
            throw new ConfigurationException($message);
        } catch (\RuntimeException $e) {
            $this->assertEquals($message, $e->getMessage());
            $this->assertInstanceOf(ConfigurationException::class, $e);
        }
    }

    public function testException_canBeCaughtAsGeneralException(): void
    {
        $message = 'General configuration error';
        
        try {
            throw new ConfigurationException($message);
        } catch (\Exception $e) {
            $this->assertEquals($message, $e->getMessage());
            $this->assertInstanceOf(ConfigurationException::class, $e);
        }
    }

    public function testException_withComplexMessage(): void
    {
        $message = 'Configuration validation failed: API key is invalid, URL is malformed, timeout value is negative';
        $exception = new ConfigurationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('API key', $exception->getMessage());
        $this->assertStringContainsString('URL is malformed', $exception->getMessage());
        $this->assertStringContainsString('timeout value', $exception->getMessage());
    }

    public function testException_withUnicodeMessage(): void
    {
        $message = '配置错误：API密钥无效 🚫 请检查设置 ⚙️';
        $exception = new ConfigurationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString('配置错误', $exception->getMessage());
        $this->assertStringContainsString('API密钥无效', $exception->getMessage());
    }

    public function testException_withJsonMessage(): void
    {
        $errorData = [
            'error' => 'configuration_invalid',
            'details' => ['field' => 'api_key', 'reason' => 'missing_or_empty']
        ];
        $message = json_encode($errorData);
        $exception = new ConfigurationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertJson($exception->getMessage());
    }

    public function testException_stackTraceContainsCorrectFile(): void
    {
        try {
            throw new ConfigurationException('Stack trace test');
        } catch (ConfigurationException $e) {
            $trace = $e->getTrace();
            $this->assertNotEmpty($trace);
            
            // 检查栈追踪包含测试文件相关信息
            $traceAsString = $e->getTraceAsString();
            $this->assertIsString($traceAsString);
            $this->assertStringContainsString('ConfigurationExceptionTest', $traceAsString);
        }
    }

    public function testException_toStringFormat(): void
    {
        $message = 'String representation test';
        $exception = new ConfigurationException($message);

        $stringRepresentation = (string) $exception;
        
        $this->assertStringContainsString('ConfigurationException', $stringRepresentation);
        $this->assertStringContainsString($message, $stringRepresentation);
        $this->assertStringContainsString(__FILE__, $stringRepresentation);
    }

    public function testException_chaining(): void
    {
        $rootCause = new \InvalidArgumentException('Root cause');
        $intermediate = new \RuntimeException('Intermediate', 0, $rootCause);
        $configException = new ConfigurationException('Configuration failed', 0, $intermediate);

        $this->assertSame($intermediate, $configException->getPrevious());
        $this->assertSame($rootCause, $configException->getPrevious()->getPrevious());
    }

    public function testException_negativeCode(): void
    {
        $message = 'Negative code test';
        $code = -500;
        $exception = new ConfigurationException($message, $code);

        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testException_largeCode(): void
    {
        $message = 'Large code test';
        $code = PHP_INT_MAX;
        $exception = new ConfigurationException($message, $code);

        $this->assertEquals($code, $exception->getCode());
        $this->assertEquals($message, $exception->getMessage());
    }

    public function testException_multilineMessage(): void
    {
        $message = "Configuration error:\nLine 1: API key missing\nLine 2: Invalid URL format\nLine 3: Timeout too low";
        $exception = new ConfigurationException($message);

        $this->assertEquals($message, $exception->getMessage());
        $this->assertStringContainsString("\n", $exception->getMessage());
        $this->assertStringContainsString('API key missing', $exception->getMessage());
    }
} 