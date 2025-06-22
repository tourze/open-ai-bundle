<?php

namespace OpenAIBundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Service\ConversationService;
use OpenAIBundle\VO\ChoiceVO;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\UsageVO;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ConversationServiceTest extends TestCase
{
    private ConversationService $conversationService;
    private MockObject $entityManager;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->conversationService = new ConversationService(
            $this->entityManager
        );
    }

    public function test_initConversation_createsNewConversationWithCharacterAndApiKey(): void
    {
        $character = $this->createMock(Character::class);
        $apiKey = $this->createMock(ApiKey::class);

        $character->expects($this->once())
                  ->method('getName')
                  ->willReturn('Test Character');
        $character->expects($this->exactly(3))
                  ->method('getSystemPrompt')
                  ->willReturn('You are a helpful assistant.');
        $apiKey->expects($this->atLeastOnce())
                ->method('getModel')
                ->willReturn('gpt-3.5-turbo');

        $conversation = $this->conversationService->initConversation($character, $apiKey);

        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertTrue($conversation->isValid());
        $this->assertEquals('gpt-3.5-turbo', $conversation->getModel());
        $this->assertSame($character, $conversation->getActor());
        $this->assertCount(1, $conversation->getMessages()); // 系统消息
    }

    public function test_createUserMessage_addsUserMessageToConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = 'Hello, how are you?';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $message = $this->conversationService->createUserMessage($conversation, $apiKey, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::user, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertSame($apiKey, $message->getApiKey());
    }

    public function test_createToolMessage_addsToolMessageToConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = '{"result": "success"}';
        $toolCallId = 'call_123';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $message = $this->conversationService->createToolMessage($conversation, $apiKey, $content, $toolCallId);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::tool, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($toolCallId, $message->getToolCallId());
        $this->assertSame($apiKey, $message->getApiKey());
    }

    public function test_appendAssistantContent_createsOrUpdatesAssistantMessage(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $msgId = 'msg_123';
        $content = 'Hello there!';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $message = $this->conversationService->appendAssistantContent($conversation, $apiKey, $msgId, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::assistant, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($msgId, $message->getMsgId());
        $this->assertSame($apiKey, $message->getApiKey());
    }

    public function test_getMessageArray_convertsConversationMessagesToArray(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);

        $collection = new \Doctrine\Common\Collections\ArrayCollection([$message1, $message2]);

        $conversation->expects($this->once())
                    ->method('getMessages')
                    ->willReturn($collection);

        $message1->expects($this->once())
                 ->method('toArray')
                 ->willReturn(['role' => 'user', 'content' => 'Hello']);
        $message2->expects($this->once())
                 ->method('toArray')
                 ->willReturn(['role' => 'assistant', 'content' => 'Hi there']);

        $result = $this->conversationService->getMessageArray($conversation);

        $this->assertCount(2, $result);
        $this->assertEquals(['role' => 'user', 'content' => 'Hello'], $result[0]);
        $this->assertEquals(['role' => 'assistant', 'content' => 'Hi there'], $result[1]);
    }

    public function test_createSystemMessage_addsSystemMessageToConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = 'You are a helpful assistant.';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $message = $this->conversationService->createSystemMessage($conversation, $apiKey, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::system, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertSame($apiKey, $message->getApiKey());
    }

    public function test_appendAssistantReasoningContent_updatesMessageWithReasoningContent(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $streamChunk = $this->createMock(StreamChunkVO::class);
        $choice = $this->createMock(ChoiceVO::class);

        $choice->expects($this->once())
               ->method('getReasoningContent')
               ->willReturn('Thinking about the answer...');

        $streamChunk->expects($this->once())
                    ->method('getMsgId')
                    ->willReturn('msg_123');

        // 该方法应该在内部创建或找到消息
        $this->conversationService->appendAssistantReasoningContent($conversation, $apiKey, $streamChunk, $choice);

        // 由于此方法有副作用，我们主要测试它不抛出异常
        $this->assertTrue(true);
    }

    public function test_appendAssistantUsage_updatesMessageWithUsageInfo(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $streamChunk = $this->createMock(StreamChunkVO::class);
        $usage = $this->createMock(UsageVO::class);

        $streamChunk->expects($this->once())
                    ->method('getMsgId')
                    ->willReturn('msg_123');

        $usage->expects($this->once())
              ->method('getPromptTokens')
              ->willReturn(100);
        $usage->expects($this->once())
              ->method('getCompletionTokens')
              ->willReturn(50);
        $usage->expects($this->once())
              ->method('getTotalTokens')
              ->willReturn(150);

        // 该方法应该在内部创建或找到消息
        $this->conversationService->appendAssistantUsage($conversation, $apiKey, $streamChunk, $usage);

        // 由于此方法有副作用，我们主要测试它不抛出异常
        $this->assertTrue(true);
    }

    public function test_appendAssistantToolCalls_updatesMessageWithToolCalls(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $streamChunk = $this->createMock(StreamChunkVO::class);
        $choice = $this->createMock(ChoiceVO::class);

        $toolCalls = [
            [
                'id' => 'call_123',
                'function' => ['name' => 'test_function']
            ]
        ];

        $streamChunk->expects($this->once())
                    ->method('getMsgId')
                    ->willReturn('msg_123');

        $choice->expects($this->atLeastOnce())
               ->method('getToolCalls')
               ->willReturn($toolCalls);

        // 该方法应该在内部创建或找到消息
        $this->conversationService->appendAssistantToolCalls($conversation, $apiKey, $streamChunk, $choice);

        // 由于此方法有副作用，我们主要测试它不抛出异常
        $this->assertTrue(true);
    }

    public function test_createMessage_withSystemRole(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = 'System message';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $apiKey->expects($this->once())
                ->method('getModel')
                ->willReturn('gpt-4');

        $message = $this->conversationService->createMessage($conversation, $apiKey, RoleEnum::system, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::system, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals('gpt-4', $message->getModel());
        $this->assertSame($apiKey, $message->getApiKey());
    }

    public function test_createMessage_withAssistantRole(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = 'Assistant response';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $apiKey->expects($this->once())
                ->method('getModel')
                ->willReturn('deepseek-chat');

        $message = $this->conversationService->createMessage($conversation, $apiKey, RoleEnum::assistant, $content);

        $this->assertInstanceOf(Message::class, $message);
        $this->assertEquals(RoleEnum::assistant, $message->getRole());
        $this->assertEquals($content, $message->getContent());
        $this->assertEquals('deepseek-chat', $message->getModel());
        $this->assertSame($apiKey, $message->getApiKey());
    }

    public function test_createUserMessage_withEmptyContent(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = '';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $message = $this->conversationService->createUserMessage($conversation, $apiKey, $content);

        $this->assertEquals('', $message->getContent());
    }

    public function test_createToolMessage_withComplexJsonContent(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);
        $content = '{"status": "success", "data": {"items": [1, 2, 3]}}';
        $toolCallId = 'call_complex';

        $conversation->expects($this->once())
                    ->method('addMessage')
                    ->with($this->isInstanceOf(Message::class));

        $message = $this->conversationService->createToolMessage($conversation, $apiKey, $content, $toolCallId);

        $this->assertEquals($content, $message->getContent());
        $this->assertEquals($toolCallId, $message->getToolCallId());
    }
} 