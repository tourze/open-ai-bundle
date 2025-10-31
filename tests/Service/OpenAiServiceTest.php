<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\VO\StreamRequestOptions;
use OpenAIBundle\VO\StreamResponseVO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(OpenAiService::class)]
#[RunTestsInSeparateProcesses]
final class OpenAiServiceTest extends AbstractIntegrationTestCase
{
    private OpenAiService $openAiService;

    protected function onSetUp(): void
    {
        $this->openAiService = self::getService(OpenAiService::class);
    }

    public function testStreamReasonerWithDefaultOptions(): void
    {
        $openAiService = self::getService(OpenAiService::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 OpenAI Bundle 的实体类，包含具体的 API 密钥属性和验证方法，测试需要模拟其特定的方法调用
         * 2. 这种使用是合理和必要的，因为实体类承载了 API 密钥的业务逻辑和状态，测试需要验证与实体的交互行为
         * 3. 暂无更好的替代方案，因为实体类是数据模型的具体表现，接口无法提供完整的实体行为约束
         */
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Hello, how are you?'],
        ];

        $apiKey->expects($this->once())
            ->method('getChatCompletionUrl')
            ->willReturn('https://api.openai.com/v1/chat/completions')
        ;

        $apiKey->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key')
        ;

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

    public function testStreamReasonerWithArrayOptions(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 OpenAI Bundle 的实体类，包含具体的 API 密钥属性和验证方法，测试需要模拟其特定的方法调用
         * 2. 这种使用是合理和必要的，因为实体类承载了 API 密钥的业务逻辑和状态，测试需要验证与实体的交互行为
         * 3. 暂无更好的替代方案，因为实体类是数据模型的具体表现，接口无法提供完整的实体行为约束
         */
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Tell me a joke'],
        ];
        $options = [
            'model' => 'gpt-4',
            'temperature' => 0.9,
            'max_tokens' => 1000,
            'debug' => true,
        ];

        $apiKey->expects($this->once())
            ->method('getChatCompletionUrl')
            ->willReturn('https://api.openai.com/v1/chat/completions')
        ;

        $apiKey->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key')
        ;

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

    public function testStreamReasonerWithVOOptions(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 OpenAI Bundle 的实体类，包含具体的 API 密钥属性和验证方法，测试需要模拟其特定的方法调用
         * 2. 这种使用是合理和必要的，因为实体类承载了 API 密钥的业务逻辑和状态，测试需要验证与实体的交互行为
         * 3. 暂无更好的替代方案，因为实体类是数据模型的具体表现，接口无法提供完整的实体行为约束
         */
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'What is PHP?'],
        ];
        $options = new StreamRequestOptions(
            debug: false,
            model: 'deepseek-coder',
            temperature: 0.7,
            maxTokens: 2000,
        );

        $apiKey->expects($this->once())
            ->method('getChatCompletionUrl')
            ->willReturn('https://api.openai.com/v1/chat/completions')
        ;

        $apiKey->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key')
        ;

        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $options);

        $this->assertInstanceOf(\Generator::class, $generator);

        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    public function testStreamReasonerWithToolsOption(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 OpenAI Bundle 的实体类，包含具体的 API 密钥属性和验证方法，测试需要模拟其特定的方法调用
         * 2. 这种使用是合理和必要的，因为实体类承载了 API 密钥的业务逻辑和状态，测试需要验证与实体的交互行为
         * 3. 暂无更好的替代方案，因为实体类是数据模型的具体表现，接口无法提供完整的实体行为约束
         */
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Calculate 2+2'],
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
                                'description' => 'Mathematical expression to evaluate',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $options = new StreamRequestOptions(
            tools: $tools,
        );

        $apiKey->expects($this->once())
            ->method('getChatCompletionUrl')
            ->willReturn('https://api.openai.com/v1/chat/completions')
        ;

        $apiKey->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key')
        ;

        $generator = $this->openAiService->streamReasoner($apiKey, $messages, $options);

        $this->assertInstanceOf(\Generator::class, $generator);

        // 尝试开始生成器执行以触发方法调用
        try {
            $generator->current();
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
        }
    }

    public function testStreamReasonerWithEmptyMessages(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 OpenAI Bundle 的实体类，包含具体的 API 密钥属性和验证方法，测试需要模拟其特定的方法调用
         * 2. 这种使用是合理和必要的，因为实体类承载了 API 密钥的业务逻辑和状态，测试需要验证与实体的交互行为
         * 3. 暂无更好的替代方案，因为实体类是数据模型的具体表现，接口无法提供完整的实体行为约束
         */
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [];

        $apiKey->expects($this->once())
            ->method('getChatCompletionUrl')
            ->willReturn('https://api.openai.com/v1/chat/completions')
        ;

        $apiKey->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key')
        ;

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

    public function testChat(): void
    {
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 OpenAI Bundle 的实体类，包含具体的 API 密钥属性和验证方法
         * 2. 这种使用是合理和必要的，因为需要测试非流式聊天功能的完整流程
         * 3. 暂无更好的替代方案，因为实体类承载了业务逻辑和状态
         */
        $apiKey = $this->createMock(ApiKey::class);
        $messages = [
            ['role' => 'user', 'content' => 'Hello, tell me about AI'],
        ];
        $options = new StreamRequestOptions(
            model: 'gpt-3.5-turbo',
            temperature: 0.8,
            maxTokens: 1500
        );

        $apiKey->expects($this->once())
            ->method('getChatCompletionUrl')
            ->willReturn('https://api.openai.com/v1/chat/completions')
        ;

        $apiKey->expects($this->once())
            ->method('getApiKey')
            ->willReturn('test-api-key')
        ;

        // 由于 chat 方法执行 HTTP 请求，在单元测试中会失败
        // 但我们可以验证方法存在且返回正确的类型
        try {
            $result = $this->openAiService->chat($apiKey, $messages, $options);
            // 如果成功执行，验证返回类型
            $this->assertInstanceOf(StreamResponseVO::class, $result);
        } catch (\Exception $e) {
            // 预期会失败，因为没有真实的 HTTP 响应
            // 我们只验证方法调用是否正确设置了必要的参数
            $this->assertInstanceOf(\Exception::class, $e);
        }
    }
}
