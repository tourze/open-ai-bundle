<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\ApiKeyCrudController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Repository\ApiKeyRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ApiKeyCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ApiKeyCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /** @return ApiKeyCrudController */
    protected function getControllerService(): ApiKeyCrudController
    {
        return new ApiKeyCrudController();
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '有效状态' => ['有效状态'];
        yield '密钥标题' => ['密钥标题'];
        yield 'API密钥' => ['API密钥'];
        yield '调用模型' => ['调用模型'];
        yield '聊天补全接口URL' => ['聊天补全接口URL'];
        yield '支持函数调用' => ['支持函数调用'];
        yield '上下文长度' => ['上下文长度'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'apiKey' => ['apiKey'];
        yield 'model' => ['model'];
        yield 'chatCompletionUrl' => ['chatCompletionUrl'];
        yield 'valid' => ['valid'];
        yield 'functionCalling' => ['functionCalling'];
        yield 'contextLength' => ['contextLength'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }

    public function testGetApiKeyIndexPageReturnsSuccessful(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestApiKeys();

        $client->request('GET', '/admin/open-ai/api-key');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testGetApiKeyNewPageReturnsSuccessful(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $client->request('GET', '/admin/open-ai/api-key/new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('<form', $content);
        $this->assertStringContainsString('ApiKey[title]', $content);
        $this->assertStringContainsString('ApiKey[apiKey]', $content);
        $this->assertStringContainsString('ApiKey[model]', $content);
        $this->assertStringContainsString('ApiKey[chatCompletionUrl]', $content);
    }

    public function testCreateApiKeyWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $client->request('POST', '/admin/open-ai/api-key/new', [
            'ApiKey' => [
                'title' => 'Test API Key',
                'apiKey' => 'sk-test123456789',
                'model' => 'gpt-3.5-turbo',
                'chatCompletionUrl' => 'https://api.openai.com/v1/chat/completions',
                'valid' => true,
                'functionCalling' => true,
                'contextLength' => ContextLength::K_4->value,
            ],
        ]);

        $this->assertTrue($client->getResponse()->isRedirection());

        /** @var ApiKeyRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\ApiKeyRepository');
        $apiKey = $repository->findOneBy(['title' => 'Test API Key']);
        $this->assertNotNull($apiKey);
        $this->assertEquals('sk-test123456789', $apiKey->getApiKey());
        $this->assertEquals('gpt-3.5-turbo', $apiKey->getModel());
        $this->assertTrue($apiKey->isValid());
        $this->assertTrue($apiKey->isFunctionCalling());
    }

    public function testEditApiKeyWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');
        $em = self::getEntityManager();

        $apiKey = $this->createTestApiKeys()[0];
        $apiKeyId = $apiKey->getId();

        $client->request('POST', sprintf('/admin/open-ai/api-key/%d/edit', $apiKey->getId()), [
            'ApiKey' => [
                'title' => 'Updated API Key',
                'apiKey' => 'sk-updated123456789',
                'model' => 'gpt-4',
                'chatCompletionUrl' => 'https://api.openai.com/v1/chat/completions',
                'valid' => true,
                'functionCalling' => true,
                'contextLength' => '8192',
            ],
        ]);

        $this->assertTrue($client->getResponse()->isRedirection());

        // 重新从数据库获取实体以避免实体管理问题
        /** @var ApiKeyRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\ApiKeyRepository');
        $updatedApiKey = $repository->find($apiKeyId);
        $this->assertNotNull($updatedApiKey);
        $this->assertEquals('Updated API Key', $updatedApiKey->getTitle());
        $this->assertEquals('sk-updated123456789', $updatedApiKey->getApiKey());
        $this->assertEquals('gpt-4', $updatedApiKey->getModel());
    }

    public function testDetailApiKeyPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $apiKey = $this->createTestApiKeys()[0];

        $client->request('GET', sprintf('/admin/open-ai/api-key/%d', $apiKey->getId()));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('field-group', $content);
        $apiKeyTitle = $apiKey->getTitle();
        self::assertNotNull($apiKeyTitle);
        $this->assertStringContainsString($apiKeyTitle, $content);
    }

    public function testCreateApiKeyWithMissingRequiredFields(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $client->request('POST', '/admin/open-ai/api-key/new', [
            'ApiKey' => [
                'title' => '',
                'apiKey' => '',
                'model' => '',
                'chatCompletionUrl' => '',
            ],
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testCreateApiKeyWithInvalidUrl(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $client->request('POST', '/admin/open-ai/api-key/new', [
            'ApiKey' => [
                'title' => 'Test API Key',
                'apiKey' => 'sk-test123456789',
                'model' => 'gpt-3.5-turbo',
                'chatCompletionUrl' => 'invalid-url',
            ],
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testCreateApiKeyWithTooLongTitle(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $client->request('POST', '/admin/open-ai/api-key/new', [
            'ApiKey' => [
                'title' => str_repeat('a', 101),
                'apiKey' => 'sk-test123456789',
                'model' => 'gpt-3.5-turbo',
                'chatCompletionUrl' => 'https://api.openai.com/v1/chat/completions',
            ],
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testSearchApiKeysByTitle(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestApiKeys();

        $client->request('GET', '/admin/open-ai/api-key?query=OpenAI');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
        $this->assertStringContainsString('OpenAI Official', $content);
    }

    public function testSearchApiKeysByModel(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestApiKeys();

        $client->request('GET', '/admin/open-ai/api-key?query=gpt-4');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
        $this->assertStringContainsString('gpt-4', $content);
    }

    public function testFilterApiKeysByValidStatus(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestApiKeys();

        $client->request('GET', '/admin/open-ai/api-key?filters[valid][comparison]=equal&filters[valid][value]=1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testFilterApiKeysByFunctionCalling(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestApiKeys();

        $client->request('GET', '/admin/open-ai/api-key?filters[functionCalling][comparison]=equal&filters[functionCalling][value]=1');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    /**
     * @return ApiKey[]
     */
    private function createTestApiKeys(): array
    {
        $em = self::getEntityManager();

        $apiKey1 = new ApiKey();
        $apiKey1->setTitle('OpenAI Official');
        $apiKey1->setApiKey('sk-openai123456789');
        $apiKey1->setModel('gpt-3.5-turbo');
        $apiKey1->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey1->setValid(true);
        $apiKey1->setFunctionCalling(true);
        $apiKey1->setContextLength(ContextLength::K_4);

        $apiKey2 = new ApiKey();
        $apiKey2->setTitle('DeepSeek API');
        $apiKey2->setApiKey('sk-deepseek987654321');
        $apiKey2->setModel('gpt-4');
        $apiKey2->setChatCompletionUrl('https://api.deepseek.com/v1/chat/completions');
        $apiKey2->setValid(false);
        $apiKey2->setFunctionCalling(false);
        $apiKey2->setContextLength(ContextLength::K_8);

        $em->persist($apiKey1);
        $em->persist($apiKey2);
        $em->flush();

        return [$apiKey1, $apiKey2];
    }
}
