<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller;

use OpenAIBundle\Controller\ChatIndexController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Enum\ContextLength;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ChatIndexController::class)]
#[RunTestsInSeparateProcesses]
final class ChatIndexControllerTest extends AbstractWebTestCase
{
    public function testGetChatIndexPage(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建测试数据
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');
        $character->setTemperature(0.7);
        $character->setMaxTokens(2000);
        $character->setTopP(0.9);
        $character->setPresencePenalty(0.0);
        $character->setFrequencyPenalty(0.0);
        $character->setValid(true);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey->setValid(true);
        $apiKey->setFunctionCalling(false);
        $apiKey->setContextLength(ContextLength::K_4);

        $em->persist($character);
        $em->persist($apiKey);
        $em->flush();

        $client->request('GET', '/open-ai/chat');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('AI Chat', $content);
    }

    public function testGetChatIndexPageWithNoValidCharacters(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建无效的字符
        $character = new Character();
        $character->setName('Invalid Character');
        $character->setSystemPrompt('Test system prompt');
        $character->setValid(false);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey->setValid(true);

        $em->persist($character);
        $em->persist($apiKey);
        $em->flush();

        $client->request('GET', '/open-ai/chat');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('AI Chat', $content);
    }

    public function testGetChatIndexPageWithNoValidApiKeys(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建有效字符但无效API Key
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');
        $character->setValid(true);

        $apiKey = new ApiKey();
        $apiKey->setTitle('Invalid API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey->setValid(false);

        $em->persist($character);
        $em->persist($apiKey);
        $em->flush();

        $client->request('GET', '/open-ai/chat');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('AI Chat', $content);
    }

    public function testUnauthorizedAccess(): void
    {
        $client = self::createClientWithDatabase();

        // 测试未认证访问 - 聊天首页通常允许访问
        $client->request('GET', '/open-ai/chat');
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
            'POST' => $client->request('POST', '/open-ai/chat'),
            'PUT' => $client->request('PUT', '/open-ai/chat'),
            'DELETE' => $client->request('DELETE', '/open-ai/chat'),
            'PATCH' => $client->request('PATCH', '/open-ai/chat'),
            'HEAD' => $client->request('HEAD', '/open-ai/chat'),
            'OPTIONS' => $client->request('OPTIONS', '/open-ai/chat'),
            'TRACE' => $client->request('TRACE', '/open-ai/chat'),
            'PURGE' => $client->request('PURGE', '/open-ai/chat'),
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
