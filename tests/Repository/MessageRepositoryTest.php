<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Repository;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\MessageRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(MessageRepository::class)]
#[RunTestsInSeparateProcesses]
final class MessageRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('You are a helpful assistant.');
        $character->setValid(true);

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $entity = new Message();
        $entity->setMsgId('test-msg-' . uniqid());
        $entity->setRole(RoleEnum::user);
        $entity->setContent('Test message content');
        $entity->setModel('gpt-3.5-turbo');
        $entity->setConversation($conversation);

        return $entity;
    }

    protected function getRepository(): MessageRepository
    {
        return self::getService(MessageRepository::class);
    }

    private function createTestCharacter(): Character
    {
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('You are a helpful assistant.');
        $character->setValid(true);
        self::getEntityManager()->persist($character);
        self::getEntityManager()->flush();

        return $character;
    }

    private function createTestMessage(string $msgId, string $content, RoleEnum $role = RoleEnum::user): Message
    {
        $message = new Message();
        $message->setMsgId($msgId);
        $message->setContent($content);
        $message->setRole($role);
        $message->setModel('gpt-3.5-turbo');

        return $message;
    }

    public function testRepositoryService(): void
    {
        $this->assertInstanceOf(MessageRepository::class, $this->getRepository());
    }

    public function testFindByConversation(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $message1 = $this->createTestMessage('msg_test_1', 'Hello', RoleEnum::user);
        $message1->setConversation($conversation);

        $message2 = $this->createTestMessage('msg_test_2', 'Hi there!', RoleEnum::assistant);
        $message2->setConversation($conversation);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($message1);
        self::getEntityManager()->flush();

        sleep(1);

        self::getEntityManager()->persist($message2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByConversation($conversation);

        $this->assertCount(2, $result);
        $this->assertSame('Hello', $result[0]->getContent());
        $this->assertSame('Hi there!', $result[1]->getContent());
    }

    public function testGetConversationTokenCounts(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Token Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $message1 = $this->createTestMessage('msg_token_1', 'Test message 1', RoleEnum::user);
        $message1->setConversation($conversation);
        $message1->setPromptTokens(10);
        $message1->setCompletionTokens(15);
        $message1->setTotalTokens(25);

        $message2 = $this->createTestMessage('msg_token_2', 'Test response 1', RoleEnum::assistant);
        $message2->setConversation($conversation);
        $message2->setPromptTokens(20);
        $message2->setCompletionTokens(30);
        $message2->setTotalTokens(50);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($message1);
        self::getEntityManager()->persist($message2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->getConversationTokenCounts($conversation);

        $this->assertSame(30, $result['prompt_tokens']);
        $this->assertSame(45, $result['completion_tokens']);
        $this->assertSame(75, $result['total_tokens']);
    }

    public function testFindByRole(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Role Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $userMessage = $this->createTestMessage('msg_user_1', 'User message', RoleEnum::user);
        $userMessage->setConversation($conversation);

        $assistantMessage = $this->createTestMessage('msg_asst_1', 'Assistant message', RoleEnum::assistant);
        $assistantMessage->setConversation($conversation);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($userMessage);
        self::getEntityManager()->persist($assistantMessage);
        self::getEntityManager()->flush();

        $userMessages = $this->getRepository()->findByRole($conversation, 'user');
        $assistantMessages = $this->getRepository()->findByRole($conversation, 'assistant');

        $this->assertCount(1, $userMessages);
        $this->assertCount(1, $assistantMessages);
        $this->assertSame('User message', $userMessages[0]->getContent());
        $this->assertSame('Assistant message', $assistantMessages[0]->getContent());
    }

    public function testFindWithToolCalls(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Tool Call Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $messageWithTools = $this->createTestMessage('msg_tools_1', 'Message with tools', RoleEnum::assistant);
        $messageWithTools->setConversation($conversation);
        $messageWithTools->setToolCalls([
            ['id' => 'call_123', 'function' => ['name' => 'test_function']],
        ]);

        $messageWithoutTools = $this->createTestMessage('msg_no_tools_1', 'Message without tools', RoleEnum::assistant);
        $messageWithoutTools->setConversation($conversation);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($messageWithTools);
        self::getEntityManager()->persist($messageWithoutTools);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findWithToolCalls($conversation);

        $this->assertCount(1, $result);
        $this->assertSame('Message with tools', $result[0]->getContent());
        $this->assertNotNull($result[0]->getToolCalls());
    }

    public function testFindByToolCallId(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Tool Call ID Test');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $message = $this->createTestMessage('msg_tool_resp_1', 'Tool response', RoleEnum::tool);
        $message->setConversation($conversation);
        $message->setToolCallId('call_123456');

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($message);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByToolCallId('call_123456');

        $this->assertInstanceOf(Message::class, $result);
        $this->assertSame('Tool response', $result->getContent());
        $this->assertSame('call_123456', $result->getToolCallId());
    }

    public function testFindByToolCallIdReturnsNull(): void
    {
        $result = $this->getRepository()->findByToolCallId('nonexistent_call_id');

        $this->assertNull($result);
    }

    public function testFindOneByOrderByLogic(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $message1 = $this->createTestMessage('msg_first', 'First Message');
        $message1->setConversation($conversation);
        $message1->setCreateTime(new \DateTimeImmutable('2023-01-01 10:00:00'));

        $message2 = $this->createTestMessage('msg_second', 'Second Message');
        $message2->setConversation($conversation);
        $message2->setCreateTime(new \DateTimeImmutable('2023-01-01 11:00:00'));

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($message1);
        self::getEntityManager()->persist($message2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findOneBy(['role' => RoleEnum::user], ['createTime' => 'ASC']);

        $this->assertInstanceOf(Message::class, $result);
        $this->assertSame($message1->getId(), $result->getId());
    }

    public function testSaveAndRemoveMethods(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $message = $this->createTestMessage('msg_save_remove', 'Save Remove Test');
        $message->setConversation($conversation);

        self::getEntityManager()->persist($conversation);

        // Test save
        $this->getRepository()->save($message);

        // Verify saved
        $this->assertNotNull($message->getId());
        $savedMessage = $this->getRepository()->find($message->getId());
        $this->assertInstanceOf(Message::class, $savedMessage);
        $this->assertSame('Save Remove Test', $savedMessage->getContent());

        // Test remove
        $messageId = $message->getId();
        $this->getRepository()->remove($message);
        $removedMessage = $this->getRepository()->find($messageId);
        $this->assertNull($removedMessage);
    }

    public function testFindByNullableFields(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        $messageWithoutTools = $this->createTestMessage('msg_no_tools', 'Message without tools');
        $messageWithoutTools->setConversation($conversation);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->persist($messageWithoutTools);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->createQueryBuilder('m')
            ->where('m.toolCalls IS NULL')
            ->andWhere('m.content = :content')
            ->setParameter('content', 'Message without tools')
            ->getQuery()
            ->getResult()
        ;

        $this->assertCount(1, $result);
        $this->assertIsArray($result);
        /** @var Message $message */
        $message = $result[0];
        $this->assertSame('Message without tools', $message->getContent());
    }

    public function testFindByConversationRelation(): void
    {
        $character = $this->createTestCharacter();

        $conversation1 = new Conversation();
        $conversation1->setTitle('First Conversation');
        $conversation1->setValid(true);
        $conversation1->setActor($character);

        $conversation2 = new Conversation();
        $conversation2->setTitle('Second Conversation');
        $conversation2->setValid(true);
        $conversation2->setActor($character);

        $message1 = $this->createTestMessage('msg_conv1', 'Message for conversation 1');
        $message1->setConversation($conversation1);

        $message2 = $this->createTestMessage('msg_conv2', 'Message for conversation 2');
        $message2->setConversation($conversation2);

        self::getEntityManager()->persist($conversation1);
        self::getEntityManager()->persist($conversation2);
        self::getEntityManager()->persist($message1);
        self::getEntityManager()->persist($message2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findBy(['conversation' => $conversation1]);

        $this->assertCount(1, $result);
        $this->assertSame('Message for conversation 1', $result[0]->getContent());
        $conversation = $result[0]->getConversation();
        $this->assertInstanceOf(Conversation::class, $conversation);
        $this->assertSame($conversation1->getId(), $conversation->getId());
    }
}
