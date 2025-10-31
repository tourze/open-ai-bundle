<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller;

use OpenAIBundle\Controller\ChatCreateController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Enum\ContextLength;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;

/**
 * @internal
 */
#[CoversClass(ChatCreateController::class)]
#[RunTestsInSeparateProcesses]
final class ChatCreateControllerTest extends AbstractWebTestCase
{
    public function testPostChatCreateWithValidData(): void
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

        $client->request('POST', '/open-ai/chat/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'characterId' => $character->getId(),
            'apiKeyId' => $apiKey->getId(),
        ]));

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('application/json', $response->headers->get('content-type'));

        $responseData = json_decode((string) $client->getResponse()->getContent(), true);
        $this->assertIsArray($responseData);
        $this->assertArrayHasKey('conversationId', $responseData);
        $this->assertArrayHasKey('description', $responseData);
        $this->assertIsString($responseData['conversationId']);
    }

    public function testPostChatCreateReturns400WhenCharacterNotFound(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建API Key但不创建Character
        $apiKey = new ApiKey();
        $apiKey->setTitle('Test API Key');
        $apiKey->setApiKey('test-key-123');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey->setValid(true);
        $apiKey->setFunctionCalling(false);
        $apiKey->setContextLength(ContextLength::K_4);

        $em->persist($apiKey);
        $em->flush();

        $client->request('POST', '/open-ai/chat/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'characterId' => 999999,
            'apiKeyId' => $apiKey->getId(),
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPostChatCreateReturns400WhenApiKeyNotFound(): void
    {
        $client = self::createClientWithDatabase();
        $em = self::getEntityManager();

        // 创建Character但不创建API Key
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('Test system prompt');
        $character->setTemperature(0.7);
        $character->setMaxTokens(2000);
        $character->setTopP(0.9);
        $character->setPresencePenalty(0.0);
        $character->setFrequencyPenalty(0.0);
        $character->setValid(true);

        $em->persist($character);
        $em->flush();

        $client->request('POST', '/open-ai/chat/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'characterId' => $character->getId(),
            'apiKeyId' => 999999,
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPostChatCreateReturns400WhenMissingCharacterId(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/open-ai/chat/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'apiKeyId' => 1,
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testPostChatCreateReturns400WhenMissingApiKeyId(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('POST', '/open-ai/chat/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'characterId' => 1,
        ]));

        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testUnauthorizedAccess(): void
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

        // 测试未认证访问 - 聊天创建接口通常允许访问
        $client->request('POST', '/open-ai/chat/create', [], [], [
            'CONTENT_TYPE' => 'application/json',
        ], (string) json_encode([
            'characterId' => $character->getId(),
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

        $client = self::createClientWithDatabase();

        if ('OPTIONS' === $method) {
            $client->request('OPTIONS', '/open-ai/chat/create');
            // OPTIONS 可能返回 200 或 405，取决于配置
            $this->assertContains($client->getResponse()->getStatusCode(), [200, 405]);
        } else {
            $this->expectException(MethodNotAllowedHttpException::class);

            match ($method) {
                'GET' => $client->request('GET', '/open-ai/chat/create'),
                'PUT' => $client->request('PUT', '/open-ai/chat/create'),
                'DELETE' => $client->request('DELETE', '/open-ai/chat/create'),
                'PATCH' => $client->request('PATCH', '/open-ai/chat/create'),
                'HEAD' => $client->request('HEAD', '/open-ai/chat/create'),
                'TRACE' => $client->request('TRACE', '/open-ai/chat/create'),
                'PURGE' => $client->request('PURGE', '/open-ai/chat/create'),
                default => self::fail("Unsupported HTTP method: {$method}"),
            };
        }
    }
}
