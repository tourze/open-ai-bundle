<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller;

use OpenAIBundle\Controller\ConversationMessagesController;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationMessagesController::class)]
#[RunTestsInSeparateProcesses]
final class ConversationMessagesControllerTest extends AbstractWebTestCase
{
    public function testGetConversationMessagesReturns404WhenConversationNotFound(): void
    {
        $client = self::createClientWithDatabase();
        $client->request('GET', '/open-ai/conversation/999999/messages');

        $this->assertEquals(404, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertEquals('Conversation not found', $responseData['error']);
    }

    public function testGetConversationMessagesReturnsEmptyMessagesForConversationWithNoMessages(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Empty conversation');
        $conversation->setDescription('Empty conversation');
        $conversation->setActor($character);

        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        $client->request('GET', '/open-ai/conversation/' . $conversation->getId() . '/messages');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertEquals([], $responseData['messages']);
        $this->assertEquals(0, $responseData['total']);
    }

    public function testGetConversationMessagesReturnsMessagesForConversationWithMessages(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Conversation with messages');
        $conversation->setDescription('Conversation with messages');
        $conversation->setActor($character);

        $message1 = new Message();
        $message1->setMsgId('msg-1');
        $message1->setConversation($conversation);
        $message1->setRole(RoleEnum::user);
        $message1->setContent('Hello');
        $message1->setModel('gpt-3.5-turbo');

        $message2 = new Message();
        $message2->setMsgId('msg-2');
        $message2->setConversation($conversation);
        $message2->setRole(RoleEnum::assistant);
        $message2->setContent('Hi there!');
        $message2->setToolCalls(['tool' => 'test']);
        $message2->setModel('gpt-3.5-turbo');

        $em->persist($character);
        $em->persist($conversation);
        $em->persist($message1);
        $em->persist($message2);
        $em->flush();

        $client->request('GET', '/open-ai/conversation/' . $conversation->getId() . '/messages');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(2, $responseData['messages']);
        $this->assertEquals(2, $responseData['total']);

        // 验证第一条消息
        $this->assertEquals($message1->getId(), $responseData['messages'][0]['id']);
        $this->assertEquals('user', $responseData['messages'][0]['role']);
        $this->assertEquals('Hello', $responseData['messages'][0]['content']);
        $this->assertArrayHasKey('createdAt', $responseData['messages'][0]);
        $this->assertArrayNotHasKey('toolCalls', $responseData['messages'][0]);

        // 验证第二条消息
        $this->assertEquals($message2->getId(), $responseData['messages'][1]['id']);
        $this->assertEquals('assistant', $responseData['messages'][1]['role']);
        $this->assertEquals('Hi there!', $responseData['messages'][1]['content']);
        $this->assertArrayHasKey('createdAt', $responseData['messages'][1]);
        $this->assertArrayHasKey('toolCalls', $responseData['messages'][1]);
        $this->assertEquals(['tool' => 'test'], $responseData['messages'][1]['toolCalls']);
    }

    public function testGetConversationMessagesFormatsMessagesCorrectly(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $message = new Message();
        $message->setMsgId('test-id');
        $message->setConversation($conversation);
        $message->setRole(RoleEnum::user);
        $message->setContent('Test content');
        $message->setModel('gpt-3.5-turbo');

        $em->persist($character);
        $em->persist($conversation);
        $em->persist($message);
        $em->flush();

        $client->request('GET', '/open-ai/conversation/' . $conversation->getId() . '/messages');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertEquals('application/json', $client->getResponse()->headers->get('Content-Type'));

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertCount(1, $responseData['messages']);
        $this->assertEquals(1, $responseData['total']);

        // 验证消息格式
        $messageData = $responseData['messages'][0];
        $this->assertEquals($message->getId(), $messageData['id']);
        $this->assertEquals('Test content', $messageData['content']);
        $this->assertArrayHasKey('createdAt', $messageData);
        $this->assertArrayNotHasKey('toolCalls', $messageData);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test conversation');
        $conversation->setDescription('Test conversation');
        $conversation->setActor($character);

        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        // 测试未认证访问 - 消息列表通常允许访问
        $client->request('GET', '/open-ai/conversation/' . $conversation->getId() . '/messages');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        if ('INVALID' === $method) {
            $this->assertTrue(true, 'No methods are disallowed for this route');

            return;
        }

        $client = self::createClientWithDatabase();

        match ($method) {
            'POST' => $client->request('POST', '/open-ai/conversation/1/messages'),
            'PUT' => $client->request('PUT', '/open-ai/conversation/1/messages'),
            'DELETE' => $client->request('DELETE', '/open-ai/conversation/1/messages'),
            'PATCH' => $client->request('PATCH', '/open-ai/conversation/1/messages'),
            'HEAD' => $client->request('HEAD', '/open-ai/conversation/1/messages'),
            'OPTIONS' => $client->request('OPTIONS', '/open-ai/conversation/1/messages'),
            'TRACE' => $client->request('TRACE', '/open-ai/conversation/1/messages'),
            'PURGE' => $client->request('PURGE', '/open-ai/conversation/1/messages'),
            default => self::fail("Unsupported HTTP method: {$method}"),
        };

        if ('HEAD' === $method) {
            // HEAD 方法在 GET 路由上应该返回成功
            $this->assertEquals(200, $client->getResponse()->getStatusCode());
        } elseif ('OPTIONS' === $method) {
            // OPTIONS 可能返回 200 或 405，取决于配置
            $this->assertContains($client->getResponse()->getStatusCode(), [200, 405]);
        } else {
            $this->assertEquals(405, $client->getResponse()->getStatusCode());
        }
    }
}
