<?php

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Service\ChatResponseHandler;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\Service\FunctionService;
use OpenAIBundle\Service\OpenAiService;
use OpenAIBundle\VO\ChoiceVO;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamRequestOptions;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Output\OutputInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ChatResponseHandler::class)]
#[RunTestsInSeparateProcesses]
final class ChatResponseHandlerTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // 空实现，不需要特殊设置
    }

    private ChatResponseHandler $handler;

    private function setUpTest(): void
    {
        // 创建 mock 服务并注入容器
        $openAiService = $this->createMock(OpenAiService::class);
        $conversationService = $this->createMock(ConversationService::class);
        $functionService = $this->createMock(FunctionService::class);

        // 将 mock 服务注入容器
        self::getContainer()->set(OpenAiService::class, $openAiService);
        self::getContainer()->set(ConversationService::class, $conversationService);
        self::getContainer()->set(FunctionService::class, $functionService);

        // 从容器获取被测试的服务
        $this->handler = self::getService(ChatResponseHandler::class);
    }

    public function testFetchResponseNonStreamModeCallsCorrectMethod(): void
    {
        $this->setUpTest();
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为 AI 角色参数
         * 2. 这种使用是合理和必要的，因为需要测试非流式响应处理
         * 3. 暂无更好的替代方案，因为需要验证角色参数的读取
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为 API 调用的凭证
         * 2. 这种使用是合理和必要的，因为需要测试 API 参数的读取
         * 3. 暂无更好的替代方案，因为需要验证 API 键的模型参数
         */
        $apiKey = $this->createMock(ApiKey::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为对话上下文
         * 2. 这种使用是合理和必要的，因为需要测试对话消息的处理
         * 3. 暂无更好的替代方案，因为需要传递给服务方法
         */
        $conversation = $this->createMock(Conversation::class);
        $tools = [];

        // 设置 Character 的模拟方法
        $character->method('getTemperature')->willReturn(0.7);
        $character->method('getTopP')->willReturn(0.9);
        $character->method('getMaxTokens')->willReturn(1000);
        $character->method('getPresencePenalty')->willReturn(0.0);
        $character->method('getFrequencyPenalty')->willReturn(0.0);

        // 设置 ApiKey 的模拟方法
        $apiKey->method('getModel')->willReturn('gpt-3.5-turbo');

        // 模拟非流式响应
        $response = new StreamChunkVO('test-id', time(), 'gpt-3.5-turbo', 'fp-123', 'chat.completion', [], null);

        // 从容器获取 mock 服务进行验证
        $conversationService = self::getContainer()->get(ConversationService::class);
        $openAiService = self::getContainer()->get(OpenAiService::class);

        $conversationService
            ->expects($this->once())
            ->method('getMessageArray')
            ->with($conversation)
            ->willReturn([])
        ;

        $openAiService
            ->expects($this->once())
            ->method('chat')
            ->willReturn($response)
        ;

        $this->handler->fetchResponse(
            $output,
            $character,
            $apiKey,
            $conversation,
            $tools,
            false, // debug
            true,  // noStream
            false  // isQuiet
        );
    }

    public function testFetchResponseStreamModeCallsStreamReasoner(): void
    {
        $this->setUpTest();
        $output = $this->createMock(OutputInterface::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为流式响应测试的角色参数
         * 2. 这种使用是合理和必要的，因为需要测试流式响应处理逻辑
         * 3. 暂无更好的替代方案，因为需要验证角色参数的读取
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为流式 API 调用的凭证
         * 2. 这种使用是合理和必要的，因为需要测试流式 API 参数的读取
         * 3. 暂无更好的替代方案，因为需要验证 API 键的模型参数
         */
        $apiKey = $this->createMock(ApiKey::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Conversation 是 Doctrine Entity 实体类，作为流式对话上下文
         * 2. 这种使用是合理和必要的，因为需要测试流式对话消息的处理
         * 3. 暂无更好的替代方案，因为需要传递给服务方法
         */
        $conversation = $this->createMock(Conversation::class);
        $tools = [];

        // 设置 Character 的模拟方法
        $character->method('getTemperature')->willReturn(0.7);
        $character->method('getTopP')->willReturn(0.9);
        $character->method('getMaxTokens')->willReturn(1000);
        $character->method('getPresencePenalty')->willReturn(0.0);
        $character->method('getFrequencyPenalty')->willReturn(0.0);

        // 设置 ApiKey 的模拟方法
        $apiKey->method('getModel')->willReturn('gpt-3.5-turbo');

        // 模拟流式响应
        $chunk = $this->createMock(StreamChunkVO::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ChoiceVO 是值对象类，表示 AI 的选择响应
         * 2. 这种使用是合理和必要的，因为需要测试流式响应的解析
         * 3. 暂无更好的替代方案，因为需要验证内容和工具调用方法
         */
        $choice = $this->createMock(ChoiceVO::class);

        $choice->method('getContent')->willReturn('Hello');
        $choice->method('getReasoningContent')->willReturn(null);
        $choice->method('getToolCalls')->willReturn([]);

        $chunk->method('getChoices')->willReturn([$choice]);
        $chunk->method('getUsage')->willReturn(null);
        $chunk->method('getMsgId')->willReturn('msg-123');

        // 从容器获取 mock 服务进行验证
        $conversationService = self::getContainer()->get(ConversationService::class);
        $openAiService = self::getContainer()->get(OpenAiService::class);

        $conversationService
            ->expects($this->once())
            ->method('getMessageArray')
            ->with($conversation)
            ->willReturn([])
        ;

        $openAiService
            ->expects($this->once())
            ->method('streamReasoner')
            ->willReturn((function () use ($chunk) { yield $chunk; })()) // 返回生成器
        ;

        $conversationService
            ->expects($this->once())
            ->method('appendAssistantContent')
            ->with($conversation, $apiKey, 'msg-123', 'Hello')
        ;

        $output->expects($this->once())
            ->method('write')
            ->with('Hello')
        ;

        $this->handler->fetchResponse(
            $output,
            $character,
            $apiKey,
            $conversation,
            $tools,
            false, // debug
            false, // noStream
            false  // isQuiet
        );
    }

    public function testBuildRequestOptionsReturnsCorrectOptions(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为测试请求选项构建的参数
         * 2. 这种使用是合理和必要的，因为需要测试参数构建逻辑
         * 3. 暂无更好的替代方案，因为需要验证多个角色参数的读取
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为请求选项构建的 API 键参数
         * 2. 这种使用是合理和必要的，因为需要测试 API 模型参数的读取
         * 3. 暂无更好的替代方案，因为需要验证 getModel 方法的调用
         */
        $apiKey = $this->createMock(ApiKey::class);
        $tools = ['tool1'];

        $character->method('getTemperature')->willReturn(0.8);
        $character->method('getTopP')->willReturn(0.95);
        $character->method('getMaxTokens')->willReturn(2000);
        $character->method('getPresencePenalty')->willReturn(0.5);
        $character->method('getFrequencyPenalty')->willReturn(0.3);

        $apiKey->method('getModel')->willReturn('gpt-4');

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildRequestOptions');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, $character, $apiKey, $tools, true);

        $this->assertInstanceOf(StreamRequestOptions::class, $result);

        // 使用反射访问私有属性进行测试
        $reflection = new \ReflectionClass($result);

        $debugProp = $reflection->getProperty('debug');
        $debugProp->setAccessible(true);
        $this->assertTrue($debugProp->getValue($result));

        $modelProp = $reflection->getProperty('model');
        $modelProp->setAccessible(true);
        $this->assertEquals('gpt-4', $modelProp->getValue($result));

        $temperatureProp = $reflection->getProperty('temperature');
        $temperatureProp->setAccessible(true);
        $this->assertEquals(0.8, $temperatureProp->getValue($result));

        $topPProp = $reflection->getProperty('topP');
        $topPProp->setAccessible(true);
        $this->assertEquals(0.95, $topPProp->getValue($result));

        $maxTokensProp = $reflection->getProperty('maxTokens');
        $maxTokensProp->setAccessible(true);
        $this->assertEquals(2000, $maxTokensProp->getValue($result));

        $presencePenaltyProp = $reflection->getProperty('presencePenalty');
        $presencePenaltyProp->setAccessible(true);
        $this->assertEquals(0.5, $presencePenaltyProp->getValue($result));

        $frequencyPenaltyProp = $reflection->getProperty('frequencyPenalty');
        $frequencyPenaltyProp->setAccessible(true);
        $this->assertEquals(0.3, $frequencyPenaltyProp->getValue($result));

        $toolsProp = $reflection->getProperty('tools');
        $toolsProp->setAccessible(true);
        $this->assertEquals($tools, $toolsProp->getValue($result));
    }

    public function testBuildRequestOptionsEmptyToolsSetsToolsToNull(): void
    {
        $this->setUpTest();
        /*
         * 使用具体类进行 mock 的原因：
         * 1. Character 是 Doctrine Entity 实体类，作为测试空工具集合的参数
         * 2. 这种使用是合理和必要的，因为需要测试边界情况下的参数构建
         * 3. 暂无更好的替代方案，因为需要验证角色参数的读取
         */
        $character = $this->createMock(Character::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ApiKey 是 Doctrine Entity 实体类，作为空工具集合测试的 API 键参数
         * 2. 这种使用是合理和必要的，因为需要测试空工具场景下的参数构建
         * 3. 暂无更好的替代方案，因为需要验证 getModel 方法的调用
         */
        $apiKey = $this->createMock(ApiKey::class);
        $tools = [];

        $character->method('getTemperature')->willReturn(0.7);
        $character->method('getTopP')->willReturn(0.9);
        $character->method('getMaxTokens')->willReturn(1000);
        $character->method('getPresencePenalty')->willReturn(0.0);
        $character->method('getFrequencyPenalty')->willReturn(0.0);

        $apiKey->method('getModel')->willReturn('gpt-3.5-turbo');

        $reflection = new \ReflectionClass($this->handler);
        $method = $reflection->getMethod('buildRequestOptions');
        $method->setAccessible(true);

        $result = $method->invoke($this->handler, $character, $apiKey, $tools, false);

        // @var StreamRequestOptions $result
        $this->assertInstanceOf(\OpenAIBundle\VO\StreamRequestOptions::class, $result);

        $reflection = new \ReflectionClass($result);
        $toolsProp = $reflection->getProperty('tools');
        $toolsProp->setAccessible(true);
        $this->assertNull($toolsProp->getValue($result));
    }
}
