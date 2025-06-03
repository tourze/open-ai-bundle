<?php

namespace OpenAIBundle\Tests\VO;

use OpenAIBundle\VO\ToolCall;
use PHPUnit\Framework\TestCase;

class ToolCallTest extends TestCase
{
    public function testConstructor_setsAllProperties(): void
    {
        $id = 'call_123456';
        $index = '0';
        $type = 'function';
        $functionName = 'get_weather';
        $functionArguments = ['location' => 'Beijing', 'unit' => 'celsius'];

        $toolCall = new ToolCall($id, $index, $type, $functionName, $functionArguments);

        $this->assertEquals($id, $toolCall->getId());
        $this->assertEquals($functionName, $toolCall->getFunctionName());
        $this->assertEquals($functionArguments, $toolCall->getFunctionArguments());
    }

    public function testGetId_returnsCorrectId(): void
    {
        $toolCall = new ToolCall(
            'call_test_789',
            '1',
            'function',
            'calculate',
            ['x' => 5, 'y' => 3]
        );

        $this->assertEquals('call_test_789', $toolCall->getId());
    }

    public function testGetFunctionName_returnsCorrectName(): void
    {
        $toolCall = new ToolCall(
            'call_func',
            '0',
            'function',
            'send_email',
            ['to' => 'user@example.com', 'subject' => 'Test']
        );

        $this->assertEquals('send_email', $toolCall->getFunctionName());
    }

    public function testGetFunctionArguments_returnsCorrectArguments(): void
    {
        $arguments = [
            'query' => 'SELECT * FROM users',
            'limit' => 10,
            'offset' => 0
        ];

        $toolCall = new ToolCall(
            'call_db',
            '0',
            'function',
            'execute_query',
            $arguments
        );

        $this->assertEquals($arguments, $toolCall->getFunctionArguments());
    }

    public function testToolCallWithEmptyArguments(): void
    {
        $toolCall = new ToolCall(
            'call_empty',
            '0',
            'function',
            'get_random_number',
            []
        );

        $this->assertEquals([], $toolCall->getFunctionArguments());
        $this->assertEmpty($toolCall->getFunctionArguments());
    }

    public function testToolCallWithComplexArguments(): void
    {
        $complexArgs = [
            'config' => [
                'timeout' => 30,
                'retries' => 3,
                'headers' => ['Content-Type' => 'application/json']
            ],
            'data' => [
                'items' => [1, 2, 3],
                'metadata' => ['version' => '1.0']
            ]
        ];

        $toolCall = new ToolCall(
            'call_complex',
            '0',
            'function',
            'api_request',
            $complexArgs
        );

        $this->assertEquals($complexArgs, $toolCall->getFunctionArguments());
        $this->assertEquals(30, $toolCall->getFunctionArguments()['config']['timeout']);
        $this->assertEquals([1, 2, 3], $toolCall->getFunctionArguments()['data']['items']);
    }

    public function testToolCallWithStringArguments(): void
    {
        $stringArgs = [
            'text' => 'Hello, world!',
            'language' => 'en',
            'format' => 'plain'
        ];

        $toolCall = new ToolCall(
            'call_string',
            '0',
            'function',
            'translate_text',
            $stringArgs
        );

        $this->assertIsArray($toolCall->getFunctionArguments());
        $this->assertEquals('Hello, world!', $toolCall->getFunctionArguments()['text']);
        $this->assertEquals('en', $toolCall->getFunctionArguments()['language']);
    }

    public function testToolCallWithNumericArguments(): void
    {
        $numericArgs = [
            'x' => 10.5,
            'y' => 20,
            'precision' => 2
        ];

        $toolCall = new ToolCall(
            'call_math',
            '0',
            'function',
            'calculate_distance',
            $numericArgs
        );

        $this->assertEquals(10.5, $toolCall->getFunctionArguments()['x']);
        $this->assertEquals(20, $toolCall->getFunctionArguments()['y']);
        $this->assertEquals(2, $toolCall->getFunctionArguments()['precision']);
    }

    public function testToolCallWithBooleanArguments(): void
    {
        $booleanArgs = [
            'enabled' => true,
            'strict_mode' => false,
            'debug' => true
        ];

        $toolCall = new ToolCall(
            'call_bool',
            '0',
            'function',
            'configure_system',
            $booleanArgs
        );

        $this->assertTrue($toolCall->getFunctionArguments()['enabled']);
        $this->assertFalse($toolCall->getFunctionArguments()['strict_mode']);
        $this->assertTrue($toolCall->getFunctionArguments()['debug']);
    }

    public function testToolCallImmutability(): void
    {
        $originalId = 'call_immutable';
        $originalFunctionName = 'test_function';
        $originalArguments = ['param' => 'value'];

        $toolCall = new ToolCall(
            $originalId,
            '0',
            'function',
            $originalFunctionName,
            $originalArguments
        );

        // 验证属性是只读的（通过readonly关键字）
        $this->assertEquals($originalId, $toolCall->getId());
        $this->assertEquals($originalFunctionName, $toolCall->getFunctionName());
        $this->assertEquals($originalArguments, $toolCall->getFunctionArguments());

        // 创建另一个实例验证独立性
        $otherToolCall = new ToolCall(
            'call_other',
            '1',
            'function',
            'other_function',
            ['other' => 'param']
        );

        $this->assertNotEquals($toolCall->getId(), $otherToolCall->getId());
        $this->assertNotEquals($toolCall->getFunctionName(), $otherToolCall->getFunctionName());
        $this->assertNotEquals($toolCall->getFunctionArguments(), $otherToolCall->getFunctionArguments());
    }

    public function testToolCallEquality(): void
    {
        $toolCall1 = new ToolCall(
            'call_equal',
            '0',
            'function',
            'test_func',
            ['param' => 'value']
        );

        $toolCall2 = new ToolCall(
            'call_equal',
            '0',
            'function',
            'test_func',
            ['param' => 'value']
        );

        // 不同的对象实例
        $this->assertNotSame($toolCall1, $toolCall2);

        // 但属性值相同
        $this->assertEquals($toolCall1->getId(), $toolCall2->getId());
        $this->assertEquals($toolCall1->getFunctionName(), $toolCall2->getFunctionName());
        $this->assertEquals($toolCall1->getFunctionArguments(), $toolCall2->getFunctionArguments());
    }

    public function testToolCallWithDifferentIndexFormats(): void
    {
        $toolCalls = [
            new ToolCall('call_1', '0', 'function', 'func1', []),
            new ToolCall('call_2', '1', 'function', 'func2', []),
            new ToolCall('call_3', '10', 'function', 'func3', [])
        ];

        $this->assertCount(3, $toolCalls);
        $this->assertEquals('call_1', $toolCalls[0]->getId());
        $this->assertEquals('call_2', $toolCalls[1]->getId());
        $this->assertEquals('call_3', $toolCalls[2]->getId());
    }

    public function testToolCallWithSpecialCharactersInId(): void
    {
        $toolCall = new ToolCall(
            'call_special-123_test.func',
            '0',
            'function',
            'special_function',
            ['special' => 'value']
        );

        $this->assertEquals('call_special-123_test.func', $toolCall->getId());
    }

    public function testToolCallWithUnicodeInArguments(): void
    {
        $unicodeArgs = [
            'message' => '你好世界',
            'emoji' => '🚀💻✅',
            'mixed' => 'Hello 世界 🌍'
        ];

        $toolCall = new ToolCall(
            'call_unicode',
            '0',
            'function',
            'process_text',
            $unicodeArgs
        );

        $this->assertEquals('你好世界', $toolCall->getFunctionArguments()['message']);
        $this->assertEquals('🚀💻✅', $toolCall->getFunctionArguments()['emoji']);
        $this->assertEquals('Hello 世界 🌍', $toolCall->getFunctionArguments()['mixed']);
    }

    public function testToolCallScenario(): void
    {
        // 模拟实际的工具调用场景
        $weatherToolCall = new ToolCall(
            'call_weather_api',
            '0',
            'function',
            'get_current_weather',
            [
                'location' => 'Shanghai, China',
                'unit' => 'metric',
                'include_forecast' => false
            ]
        );

        $this->assertEquals('call_weather_api', $weatherToolCall->getId());
        $this->assertEquals('get_current_weather', $weatherToolCall->getFunctionName());
        $this->assertEquals('Shanghai, China', $weatherToolCall->getFunctionArguments()['location']);
        $this->assertEquals('metric', $weatherToolCall->getFunctionArguments()['unit']);
        $this->assertFalse($weatherToolCall->getFunctionArguments()['include_forecast']);
    }
} 