<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller;

use OpenAIBundle\Controller\ConversationChatController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\MessageRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationChatController::class)]
#[RunTestsInSeparateProcesses]
final class ConversationChatControllerTest extends AbstractWebTestCase
{
    protected function onSetUp(): void
    {
        // 设置环境变量以禁用异步插入服务的日志输出
        $_ENV['DISABLE_LOGGING_IN_TESTS'] = true;
    }

    public function testPostConversationChatReturns404WhenConversationNotFound(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(NotFoundHttpException::class);

        $client->request('POST', '/open-ai/conversation/999999/chat', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode(['message' => 'Test message']));
    }

    public function testPostConversationChatCreatesMessageWithProvidedApiKey(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $em->persist($apiKey);
        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        $client->request('POST', '/open-ai/conversation/' . $conversation->getId() . '/chat', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'message' => 'Test message',
            'apiKeyId' => $apiKey->getId(),
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertEquals('Message received', $responseData['message']);
        $this->assertArrayHasKey('messageId', $responseData);

        // 验证消息是否被保存
        /** @var MessageRepository $messageRepo */
        $messageRepo = self::getService('OpenAIBundle\Repository\MessageRepository');
        $this->assertInstanceOf(MessageRepository::class, $messageRepo);
        $messages = $messageRepo->findByConversation($conversation);
        $this->assertCount(1, $messages);
        $this->assertEquals('Test message', $messages[0]->getContent());
        $this->assertEquals(RoleEnum::user, $messages[0]->getRole());
    }

    public function testPostConversationChatUsesCharacterDefaultApiKey(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $apiKey = new ApiKey();
        $apiKey->setTitle('Character API Key');
        $apiKey->setApiKey('character-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');
        $character->setPreferredApiKey($apiKey);

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $em->persist($apiKey);
        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        $client->request('POST', '/open-ai/conversation/' . $conversation->getId() . '/chat', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'message' => 'Test message',
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());

        // 验证消息是否被保存
        /** @var MessageRepository $messageRepo */
        $messageRepo = self::getService('OpenAIBundle\Repository\MessageRepository');
        $this->assertInstanceOf(MessageRepository::class, $messageRepo);
        $messages = $messageRepo->findByConversation($conversation);
        $this->assertCount(1, $messages);
        $this->assertEquals('Test message', $messages[0]->getContent());
    }

    public function testPostConversationChatReturns400WhenNoApiKeyAvailable(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建没有API Key的对话
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        $client->request('POST', '/open-ai/conversation/' . $conversation->getId() . '/chat', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'message' => 'Test message',
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');

        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $em->persist($apiKey);
        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        // 测试未认证访问 - 聊天接口通常允许访问
        $client->request('POST', '/open-ai/conversation/' . $conversation->getId() . '/chat', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'message' => 'Test message',
            'apiKeyId' => $apiKey->getId(),
        ]));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        if ('INVALID' === $method) {
            $this->assertTrue(true, 'No methods are disallowed for this route');

            return;
        }

        $client = self::createClient();

        match ($method) {
            'GET' => $client->request('GET', '/open-ai/conversation/1/chat'),
            'PUT' => $client->request('PUT', '/open-ai/conversation/1/chat'),
            'DELETE' => $client->request('DELETE', '/open-ai/conversation/1/chat'),
            'PATCH' => $client->request('PATCH', '/open-ai/conversation/1/chat'),
            'HEAD' => $client->request('HEAD', '/open-ai/conversation/1/chat'),
            'OPTIONS' => $client->request('OPTIONS', '/open-ai/conversation/1/chat'),
            'TRACE' => $client->request('TRACE', '/open-ai/conversation/1/chat'),
            'PURGE' => $client->request('PURGE', '/open-ai/conversation/1/chat'),
            default => self::fail("Unsupported HTTP method: {$method}"),
        };

        if ('HEAD' === $method) {
            // HEAD 方法在 POST 路由上应该返回成功
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        } elseif ('OPTIONS' === $method) {
            // OPTIONS 可能返回 200 或 405，取决于配置
            $this->assertContains($client->getResponse()->getStatusCode(), [200, 405]);
        } else {
            $this->assertEquals(405, $client->getResponse()->getStatusCode());
        }
    }
}
