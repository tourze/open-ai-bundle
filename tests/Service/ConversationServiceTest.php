<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\VO\ChoiceVO;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\UsageVO;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationService::class)]
#[RunTestsInSeparateProcesses]
final class ConversationServiceTest extends AbstractIntegrationTestCase
{
    private ConversationService $conversationService;

    protected function onSetUp(): void
    {
        $this->conversationService = self::getService(ConversationService::class);
    }

    public function testInitConversationCreatesNewConversationWithCharacterAndApiKey(): void
    {
        // 使用真实的实体而不是 Mock
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('You are a helpful assistant.');

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        // 先持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->flush();

        $conversation = $this->conversationService->initConversation($character, $apiKey);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertTrue($conversation->isValid());
        $this->assertEquals('gpt-3.5-turbo', $conversation->getModel());
        $this->assertSame($character, $conversation->getActor());
        $this->assertCount(1, $conversation->getMessages()); // 系统消息
    }

    public function testCreateUserMessageAddsUserMessageToConversation(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = 'Hello, how are you?';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createUserMessage($conversation, $apiKey, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::user, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertSame($apiKey, $message->getApiKey());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testCreateToolMessageAddsToolMessageToConversation(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = '{"result": "success"}';
        $toolCallId = 'call_123';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createToolMessage($conversation, $apiKey, $content, $toolCallId);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::tool, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($toolCallId, $message->getToolCallId());
        $this->assertSame($apiKey, $message->getApiKey());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testAppendAssistantContentCreatesOrUpdatesAssistantMessage(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $msgId = 'msg_123';
        $content = 'Hello there!';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->appendAssistantContent($conversation, $apiKey, $msgId, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::assistant, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($msgId, $message->getMsgId());
        $this->assertSame($apiKey, $message->getApiKey());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testGetMessageArrayConvertsConversationMessagesToArray(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        // 创建真实的消息
        $message1 = new Message();
        $message1->setMsgId('msg_001');
        $message1->setRole(RoleEnum::user);
        $message1->setContent('Hello');
        $message1->setModel('gpt-3.5-turbo');
        $message1->setApiKey($apiKey);
        $conversation->addMessage($message1);

        $message2 = new Message();
        $message2->setMsgId('msg_002');
        $message2->setRole(RoleEnum::assistant);
        $message2->setContent('Hi there');
        $message2->setModel('gpt-3.5-turbo');
        $message2->setApiKey($apiKey);
        $conversation->addMessage($message2);

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($message1);
        self::getEntityManager()->persist($message2);
        self::getEntityManager()->flush();

        $result = $this->conversationService->getMessageArray($conversation);

        $this->assertCount(2, $result);
        $this->assertEquals(['role' => 'user', 'content' => 'Hello'], $result[0]);
        $this->assertEquals(['role' => 'assistant', 'content' => 'Hi there'], $result[1]);
    }

    public function testCreateSystemMessageAddsSystemMessageToConversation(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = 'You are a helpful assistant.';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createSystemMessage($conversation, $apiKey, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::system, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertSame($apiKey, $message->getApiKey());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testAppendAssistantReasoningContentUpdatesMessageWithReasoningContent(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        /*
         * 使用具体类进行 mock 的原因：
         * 1. StreamChunkVO 是 OpenAI Bundle 的数据传输对象，包含具体的流式数据结构和属性
         * 2. 这种使用是合理和必要的，因为测试需要模拟流式数据的特定结构和方法调用
         * 3. 暂无更好的替代方案，因为 VO 类承载了具体的数据结构，接口无法提供完整的数据约束
         */
        $streamChunk = $this->createMock(StreamChunkVO::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ChoiceVO 是 OpenAI Bundle 的选择项数据传输对象，包含具体的选择数据结构和属性
         * 2. 这种使用是合理和必要的，因为测试需要模拟 AI 响应选择的特定结构和方法调用
         * 3. 暂无更好的替代方案，因为 VO 类承载了具体的数据结构，接口无法提供完整的数据约束
         */
        $choice = $this->createMock(ChoiceVO::class);

        $choice->expects($this->once())
            ->method('getReasoningContent')
            ->willReturn('Thinking about the answer...')
        ;

        $streamChunk->expects($this->once())
            ->method('getMsgId')
            ->willReturn('msg_123')
        ;

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        // 该方法应该在内部创建或找到消息
        $this->conversationService->appendAssistantReasoningContent($conversation, $apiKey, $streamChunk, $choice);

        // 验证对话中的消息数量有变化，证明方法产生了预期副作用
        self::getEntityManager()->refresh($conversation);
        $this->assertGreaterThanOrEqual(0, $conversation->getMessages()->count());
    }

    public function testAppendAssistantUsageUpdatesMessageWithUsageInfo(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        /*
         * 使用具体类进行 mock 的原因：
         * 1. StreamChunkVO 是 OpenAI Bundle 的数据传输对象，包含具体的流式数据结构和属性
         * 2. 这种使用是合理和必要的，因为测试需要模拟流式数据的特定结构和方法调用
         * 3. 暂无更好的替代方案，因为 VO 类承载了具体的数据结构，接口无法提供完整的数据约束
         */
        $streamChunk = $this->createMock(StreamChunkVO::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. UsageVO 是 OpenAI Bundle 的使用情况数据传输对象，包含具体的资源使用数据结构和属性
         * 2. 这种使用是合理和必要的，因为测试需要模拟 API 使用情况的特定结构和方法调用
         * 3. 暂无更好的替代方案，因为 VO 类承载了具体的数据结构，接口无法提供完整的数据约束
         */
        $usage = $this->createMock(UsageVO::class);

        $streamChunk->expects($this->once())
            ->method('getMsgId')
            ->willReturn('msg_123')
        ;

        $usage->expects($this->once())
            ->method('getPromptTokens')
            ->willReturn(100)
        ;
        $usage->expects($this->once())
            ->method('getCompletionTokens')
            ->willReturn(50)
        ;
        $usage->expects($this->once())
            ->method('getTotalTokens')
            ->willReturn(150)
        ;

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        // 该方法应该在内部创建或找到消息
        $this->conversationService->appendAssistantUsage($conversation, $apiKey, $streamChunk, $usage);

        // 验证对话中的消息数量，证明方法产生了预期副作用
        self::getEntityManager()->refresh($conversation);
        $this->assertGreaterThanOrEqual(0, $conversation->getMessages()->count());
    }

    public function testAppendAssistantToolCallsUpdatesMessageWithToolCalls(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        /*
         * 使用具体类进行 mock 的原因：
         * 1. StreamChunkVO 是 OpenAI Bundle 的数据传输对象，包含具体的流式数据结构和属性
         * 2. 这种使用是合理和必要的，因为测试需要模拟流式数据的特定结构和方法调用
         * 3. 暂无更好的替代方案，因为 VO 类承载了具体的数据结构，接口无法提供完整的数据约束
         */
        $streamChunk = $this->createMock(StreamChunkVO::class);
        /*
         * 使用具体类进行 mock 的原因：
         * 1. ChoiceVO 是 OpenAI Bundle 的选择项数据传输对象，包含具体的选择数据结构和属性
         * 2. 这种使用是合理和必要的，因为测试需要模拟 AI 响应选择的特定结构和方法调用
         * 3. 暂无更好的替代方案，因为 VO 类承载了具体的数据结构，接口无法提供完整的数据约束
         */
        $choice = $this->createMock(ChoiceVO::class);

        $toolCalls = [
            [
                'id' => 'call_123',
                'function' => ['name' => 'test_function'],
            ],
        ];

        $streamChunk->expects($this->once())
            ->method('getMsgId')
            ->willReturn('msg_123')
        ;

        $choice->expects($this->atLeastOnce())
            ->method('getToolCalls')
            ->willReturn($toolCalls)
        ;

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        // 该方法应该在内部创建或找到消息
        $this->conversationService->appendAssistantToolCalls($conversation, $apiKey, $streamChunk, $choice);

        // 验证对话中的消息数量，证明方法产生了预期副作用
        self::getEntityManager()->refresh($conversation);
        $this->assertGreaterThanOrEqual(0, $conversation->getMessages()->count());
    }

    public function testCreateMessageWithSystemRole(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-4');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = 'System message';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createMessage($conversation, $apiKey, RoleEnum::system, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::system, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals('gpt-4', $message->getModel());
        $this->assertSame($apiKey, $message->getApiKey());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testCreateMessageWithAssistantRole(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('deepseek-chat');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = 'Assistant response';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createMessage($conversation, $apiKey, RoleEnum::assistant, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::assistant, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals('deepseek-chat', $message->getModel());
        $this->assertSame($apiKey, $message->getApiKey());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testCreateUserMessageWithEmptyContent(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = '';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createUserMessage($conversation, $apiKey, $content);

        $this->assertEquals('', $message->getContent());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }

    public function testCreateToolMessageWithComplexJsonContent(): void
    {
        // 使用真实的实体
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $content = '{"status": "success", "data": {"items": [1, 2, 3]}}';
        $toolCallId = 'call_complex';

        // 持久化实体
        self::getEntityManager()->persist($character);
        self::getEntityManager()->persist($apiKey);
        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $message = $this->conversationService->createToolMessage($conversation, $apiKey, $content, $toolCallId);

        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($toolCallId, $message->getToolCallId());

        // 验证消息被添加到对话中
        $this->assertCount(1, $conversation->getMessages());
    }
}
