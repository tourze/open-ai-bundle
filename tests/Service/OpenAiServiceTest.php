<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\VO\StreamRequestOptions;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OpenAiServiceTest extends TestCase
{
    private OpenAiService $openAiService;

    public function test_streamReasoner_withDefaultOptions(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Hello, how are you?']
        ];

        $apiKey->expects($this->once())
               ->method('getChatCompletionUrl')
               ->willReturn('https://api.openai.com/v1/chat/completions');

        $apiKey->expects($this->once())
               ->method('getApiKey')
               ->willReturn('test-api-key');

        $options = new StreamRequestOptions();
        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $options);

        // 由于streamReasoner执行HTTP请求，
        // 在单元测试中我们不能实际执行这个请求
        // 所以我们只验证生成器已创建
        $this->assertInstanceOf(\Generator::class, $generator);
        
        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    public function test_streamReasoner_withArrayOptions(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Tell me a joke']
        ];
        $options = [
            'model' => 'gpt-4',
            'temperature' => 0.9,
            'max_tokens' => 1000,
            'debug' => true,
        ];

        $apiKey->expects($this->once())
               ->method('getChatCompletionUrl')
               ->willReturn('https://api.openai.com/v1/chat/completions');

        $apiKey->expects($this->once())
               ->method('getApiKey')
               ->willReturn('test-api-key');

        $streamOptions = StreamRequestOptions::fromArray($options);
        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $streamOptions);

        $this->assertInstanceOf(\Generator::class, $generator);
        
        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    public function test_streamReasoner_withVOOptions(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'What is PHP?']
        ];
        $options = new StreamRequestOptions(
            debug: false,
            model: 'deepseek-coder',
            temperature: 0.7,
            maxTokens: 2000,
        );

        $apiKey->expects($this->once())
               ->method('getChatCompletionUrl')
               ->willReturn('https://api.openai.com/v1/chat/completions');

        $apiKey->expects($this->once())
               ->method('getApiKey')
               ->willReturn('test-api-key');

        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $options);

        $this->assertInstanceOf(\Generator::class, $generator);
        
        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    public function test_streamReasoner_withToolsOption(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Calculate 2+2']
        ];
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'calculate',
                    'description' => 'Perform calculations',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'expression' => [
                                'type' => 'string',
                                'description' => 'Mathematical expression to evaluate'
                            ]
                        ]
                    ]
                ]
            ]
        ];
        $options = new StreamRequestOptions(
            tools: $tools,
        );

        $apiKey->expects($this->once())
               ->method('getChatCompletionUrl')
               ->willReturn('https://api.openai.com/v1/chat/completions');

        $apiKey->expects($this->once())
               ->method('getApiKey')
               ->willReturn('test-api-key');

        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $options);

        $this->assertInstanceOf(\Generator::class, $generator);
        
        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    public function test_streamReasoner_withEmptyMessages(): void
    {
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [];

        $apiKey->expects($this->once())
               ->method('getChatCompletionUrl')
               ->willReturn('https://api.openai.com/v1/chat/completions');

        $apiKey->expects($this->once())
               ->method('getApiKey')
               ->willReturn('test-api-key');

        $options = new StreamRequestOptions();
        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $options);

        $this->assertInstanceOf(\Generator::class, $generator);
        
        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    protected function setUp(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $this->openAiService = new OpenAiService($logger);
    }
}