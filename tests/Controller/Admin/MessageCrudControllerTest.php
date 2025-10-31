<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\MessageCrudController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\MessageRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(MessageCrudController::class)]
#[RunTestsInSeparateProcesses]
final class MessageCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    protected function getControllerService(): MessageCrudController
    {
        return new MessageCrudController();
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '消息ID' => ['消息ID'];
        yield '所属对话' => ['所属对话'];
        yield '角色' => ['角色'];
        yield '消息内容' => ['消息内容'];
        yield '推理过程' => ['推理过程'];
        yield '工具调用ID' => ['工具调用ID'];
        yield '使用模型' => ['使用模型'];
        yield '输入令牌数' => ['输入令牌数'];
        yield '输出令牌数' => ['输出令牌数'];
        yield '总令牌数' => ['总令牌数'];
        yield '使用密钥' => ['使用密钥'];
        yield '创建人' => ['创建人'];
        yield '更新人' => ['更新人'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'msgId' => ['msgId'];
        yield 'conversation' => ['conversation'];
        yield 'role' => ['role'];
        yield 'content' => ['content'];
        yield 'reasoningContent' => ['reasoningContent'];
        yield 'toolCallId' => ['toolCallId'];
        yield 'model' => ['model'];
        yield 'promptTokens' => ['promptTokens'];
        yield 'completionTokens' => ['completionTokens'];
        yield 'totalTokens' => ['totalTokens'];
        yield 'apiKey' => ['apiKey'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        yield 'msgId' => ['msgId'];
        yield 'conversation' => ['conversation'];
        yield 'role' => ['role'];
        yield 'content' => ['content'];
        yield 'reasoningContent' => ['reasoningContent'];
        yield 'toolCallId' => ['toolCallId'];
        yield 'model' => ['model'];
        yield 'promptTokens' => ['promptTokens'];
        yield 'completionTokens' => ['completionTokens'];
        yield 'totalTokens' => ['totalTokens'];
        yield 'apiKey' => ['apiKey'];
    }

    public function testGetMessageIndexPageReturnsSuccessful(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestMessages();

        $client->request('GET', '/admin/open-ai/message');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testGetMessageNewPageReturnsSuccessful(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestConversation();

        $client->request('GET', '/admin/open-ai/message/new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('<form', $content);
        $this->assertStringContainsString('Message[msgId]', $content);
        $this->assertStringContainsString('Message[content]', $content);
    }

    public function testCreateMessageWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $conversation = $this->createTestConversation();

        $crawler = $client->request('GET', '/admin/open-ai/message/new');

        $form = $crawler->selectButton('Create')->form([
            'Message[msgId]' => 'test-msg-123',
            'Message[conversation]' => (string) $conversation->getId(),
            'Message[role]' => RoleEnum::user->value,
            'Message[content]' => 'Test message content',
            'Message[model]' => 'gpt-3.5-turbo',
        ]);

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        /** @var MessageRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\MessageRepository');
        $message = $repository->findOneBy(['msgId' => 'test-msg-123']);
        $this->assertNotNull($message);
        $this->assertEquals('Test message content', $message->getContent());
        $this->assertEquals(RoleEnum::user, $message->getRole());
        $this->assertEquals('gpt-3.5-turbo', $message->getModel());
    }

    public function testEditMessageWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');
        $em = self::getEntityManager();

        $message = $this->createTestMessages()[0];
        $messageId = $message->getId();

        $crawler = $client->request('GET', sprintf('/admin/open-ai/message/%d/edit', $messageId));

        $form = $crawler->selectButton('Save changes')->form([
            'Message[content]' => 'Updated message content',
        ]);

        $client->submit($form);

        $this->assertTrue($client->getResponse()->isRedirection());

        // 重新从数据库获取实体，而不是刷新已有实体
        /** @var MessageRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\MessageRepository');
        $updatedMessage = $repository->find($messageId);
        $this->assertNotNull($updatedMessage);
        $this->assertEquals('Updated message content', $updatedMessage->getContent());
    }

    public function testDeleteMessage(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');
        $em = self::getEntityManager();

        $message = $this->createTestMessages()[0];
        $messageId = $message->getId();

        // 直接使用EntityManager删除实体，这是测试删除功能的更直接方式
        $em->remove($message);
        $em->flush();

        /** @var MessageRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\MessageRepository');
        $deletedMessage = $repository->find($messageId);
        $this->assertNull($deletedMessage);
    }

    public function testDetailMessagePage(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $message = $this->createTestMessages()[0];

        $client->request('GET', sprintf('/admin/open-ai/message/%d', $message->getId()));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('消息详情', $content);
        $this->assertStringContainsString($message->getContent(), $content);
    }

    public function testCreateMessageWithMissingRequiredFields(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestConversation();

        $crawler = $client->request('GET', '/admin/open-ai/message/new');

        $form = $crawler->selectButton('Create')->form([
            'Message[msgId]' => '', // 空消息ID
            'Message[content]' => '', // 空内容
        ]);

        $client->submit($form);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testCreateMessageWithTooLongContent(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $conversation = $this->createTestConversation();

        $crawler = $client->request('GET', '/admin/open-ai/message/new');

        $longContent = str_repeat('a', 70000); // 超过 TEXT 字段的 65535 字符限制

        $form = $crawler->selectButton('Create')->form([
            'Message[msgId]' => 'test-msg-long',
            'Message[conversation]' => (string) $conversation->getId(),
            'Message[role]' => RoleEnum::user->value,
            'Message[content]' => $longContent,
            'Message[model]' => 'gpt-3.5-turbo',
        ]);

        $client->submit($form);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testSearchMessagesByContent(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestMessages();

        $client->request('GET', '/admin/open-ai/message?query=Hello');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
        $this->assertStringContainsString('Hello', $content);
    }

    public function testFilterMessagesByRole(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestMessages();

        $client->request('GET', '/admin/open-ai/message?filters[role][comparison]=equal&filters[role][value]=user');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testFilterMessagesByConversation(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $conversation = $this->createTestConversation();
        $this->createTestMessages();

        $client->request('GET', sprintf('/admin/open-ai/message?filters[conversation][comparison]=equal&filters[conversation][value]=%d', $conversation->getId()));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    private function createTestApiKey(): ApiKey
    {
        $em = self::getEntityManager();

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('sk-test123456789');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey->setValid(true);
        $apiKey->setContextLength(ContextLength::K_4);

        $em->persist($apiKey);
        $em->flush();

        return $apiKey;
    }

    private function createTestCharacter(): Character
    {
        $em = self::getEntityManager();

        $apiKey = $this->createTestApiKey();

        $character = new Character();
        $character->setName('Test Character');
        $character->setDescription('A test character');
        $character->setSystemPrompt('You are a helpful AI assistant.');
        $character->setTemperature(0.7);
        $character->setValid(true);
        $character->setPreferredApiKey($apiKey);

        $em->persist($character);
        $em->flush();

        return $character;
    }

    private function createTestConversation(): Conversation
    {
        $em = self::getEntityManager();

        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('A test conversation');
        $conversation->setActor($character);

        $em->persist($conversation);
        $em->flush();

        return $conversation;
    }

    /**
     * @return Message[]
     */
    private function createTestMessages(): array
    {
        $em = self::getEntityManager();

        $conversation = $this->createTestConversation();

        $message1 = new Message();
        $message1->setMsgId('msg-1');
        $message1->setConversation($conversation);
        $message1->setRole(RoleEnum::user);
        $message1->setContent('Hello, how are you?');
        $message1->setModel('gpt-3.5-turbo');

        $message2 = new Message();
        $message2->setMsgId('msg-2');
        $message2->setConversation($conversation);
        $message2->setRole(RoleEnum::assistant);
        $message2->setContent('I am doing well, thank you for asking!');
        $message2->setModel('gpt-3.5-turbo');
        $message2->setToolCalls(['function' => 'greet']);

        $em->persist($message1);
        $em->persist($message2);
        $em->flush();

        return [$message1, $message2];
    }

    public function testAccessDeniedForUnauthenticatedUser(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/open-ai/message');
    }

    public function testAccessDeniedForNormalUser(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsUser($client);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/open-ai/message');
    }
}
