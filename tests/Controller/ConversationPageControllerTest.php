<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller;

use OpenAIBundle\Controller\ConversationPageController;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationPageController::class)]
#[RunTestsInSeparateProcesses]
final class ConversationPageControllerTest extends AbstractWebTestCase
{
    public function testGetConversationPageReturns404WhenConversationNotFound(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(NotFoundHttpException::class);
        $client->request('GET', '/open-ai/conversation/999999');
    }

    public function testGetConversationPageLoadsConversationAndMessages(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test description');
        $conversation->setActor($character);

        $message = new Message();
        $message->setMsgId('test-msg-1');
        $message->setConversation($conversation);
        $message->setRole(RoleEnum::user);
        $message->setContent('Test message');
        $message->setModel('gpt-3.5-turbo');

        $em->persist($character);
        $em->persist($conversation);
        $em->persist($message);
        $em->flush();

        $client->request('GET', '/open-ai/conversation/' . $conversation->getId());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('OpenAI Chat', $content);
    }

    public function testGetConversationPageRendersEmptyMessagesCorrectly(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建没有消息的对话
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Empty Conversation');
        $conversation->setDescription('Conversation without messages');
        $conversation->setActor($character);

        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        $client->request('GET', '/open-ai/conversation/' . $conversation->getId());

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        $this->assertNotFalse($content);
        $this->assertStringContainsString('OpenAI Chat', $content);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test prompt');

        $conversation = new Conversation();
        $conversation->setTitle('Test Conversation');
        $conversation->setDescription('Test description');
        $conversation->setActor($character);

        $em->persist($character);
        $em->persist($conversation);
        $em->flush();

        // 测试未认证访问 - 对话页面通常允许访问
        $client->request('GET', '/open-ai/conversation/' . $conversation->getId());
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
            'POST' => $client->request('POST', '/open-ai/conversation/1'),
            'PUT' => $client->request('PUT', '/open-ai/conversation/1'),
            'DELETE' => $client->request('DELETE', '/open-ai/conversation/1'),
            'PATCH' => $client->request('PATCH', '/open-ai/conversation/1'),
            'HEAD' => $client->request('HEAD', '/open-ai/conversation/1'),
            'OPTIONS' => $client->request('OPTIONS', '/open-ai/conversation/1'),
            'TRACE' => $client->request('TRACE', '/open-ai/conversation/1'),
            'PURGE' => $client->request('PURGE', '/open-ai/conversation/1'),
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
