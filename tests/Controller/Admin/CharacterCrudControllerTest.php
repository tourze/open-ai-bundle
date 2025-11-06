<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\CharacterCrudController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Repository\CharacterRepository;
use PHPUnit\Framework\Attributes\Before;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(CharacterCrudController::class)]
#[RunTestsInSeparateProcesses]
final class CharacterCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    #[Before]
    protected function ensureAvatarUploadDirectory(): void
    {
        // 确保头像上传目录存在
        $kernel = self::$kernel ?? self::bootKernel();
        $projectDir = $kernel->getProjectDir();
        $uploadDir = $projectDir . '/public/uploads/avatars';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0o755, true);
        }
    }

    /** @return CharacterCrudController */
    protected function getControllerService(): CharacterCrudController
    {
        return new CharacterCrudController();
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '角色名称' => ['角色名称'];
        yield '头像' => ['头像'];
        yield '描述' => ['描述'];
        yield '系统提示词' => ['系统提示词'];
        yield '温度参数' => ['温度参数'];
        yield '采样概率阈值' => ['采样概率阈值'];
        yield '最大生成令牌数' => ['最大生成令牌数'];
        yield '存在惩罚' => ['存在惩罚'];
        yield '频率惩罚' => ['频率惩罚'];
        yield '偏好API密钥' => ['偏好API密钥'];
        yield '有效状态' => ['有效状态'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'systemPrompt' => ['systemPrompt'];
        yield 'avatar' => ['avatar'];
        yield 'temperature' => ['temperature'];
        yield 'topP' => ['topP'];
        yield 'maxTokens' => ['maxTokens'];
        yield 'presencePenalty' => ['presencePenalty'];
        yield 'frequencyPenalty' => ['frequencyPenalty'];
        yield 'preferredApiKey' => ['preferredApiKey'];
        yield 'valid' => ['valid'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }

    public function testGetCharacterIndexPageReturnsSuccessful(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->createTestCharacters();

        $client->request('GET', '/admin/open-ai/character');

        $response = $client->getResponse();
        $this->assertEquals(200, $response->getStatusCode());
        $content = $response->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testGetCharacterNewPageReturnsSuccessful(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('GET', '/admin/open-ai/character/new');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('<form', $content);
        $this->assertStringContainsString('Character[name]', $content);
        $this->assertStringContainsString('Character[systemPrompt]', $content);
        $this->assertStringContainsString('Character[description]', $content);
    }

    public function testCreateCharacterWithValidData(): void
    {
        $client = $this->createAuthenticatedClient();

        $apiKey = $this->createTestApiKey();

        $client->request('POST', '/admin/open-ai/character/new', [
            'Character' => [
                'name' => 'Test Character',
                'description' => 'A test character for testing',
                'systemPrompt' => 'You are a helpful AI assistant for testing purposes.',
                'temperature' => 0.7,
                'maxTokens' => 2000,
                'topP' => 0.9,
                'presencePenalty' => 0.0,
                'frequencyPenalty' => 0.0,
                'valid' => true,
                'preferredApiKey' => $apiKey->getId(),
            ],
        ]);
        $this->assertTrue($client->getResponse()->isRedirection());

        /** @var CharacterRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\CharacterRepository');
        $character = $repository->findOneBy(['name' => 'Test Character']);
        $this->assertNotNull($character);
        $this->assertEquals('A test character for testing', $character->getDescription());
        $this->assertEquals('You are a helpful AI assistant for testing purposes.', $character->getSystemPrompt());
        $this->assertEquals(0.7, $character->getTemperature());
        $this->assertEquals(2000, $character->getMaxTokens());
        $this->assertTrue($character->isValid());
    }

    public function testEditCharacterWithValidData(): void
    {
        $client = $this->createAuthenticatedClient();
        $em = self::getEntityManager();

        $character = $this->createTestCharacters()[0];
        $characterId = $character->getId();

        $client->request('POST', sprintf('/admin/open-ai/character/%d/edit', $character->getId()), [
            'Character' => [
                'name' => 'Updated Character',
                'description' => 'Updated description',
                'systemPrompt' => $character->getSystemPrompt(), // 保持原有值
                'temperature' => $character->getTemperature(),
                'maxTokens' => $character->getMaxTokens(),
                'topP' => $character->getTopP(),
                'presencePenalty' => $character->getPresencePenalty(),
                'frequencyPenalty' => $character->getFrequencyPenalty(),
                'valid' => $character->isValid(),
                'preferredApiKey' => $character->getPreferredApiKey()?->getId(),
            ],
        ]);
        $this->assertTrue($client->getResponse()->isRedirection());

        // 重新从数据库获取实体以避免实体管理问题
        /** @var CharacterRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\CharacterRepository');
        $updatedCharacter = $repository->find($characterId);
        $this->assertNotNull($updatedCharacter);
        $this->assertEquals('Updated Character', $updatedCharacter->getName());
        $this->assertEquals('Updated description', $updatedCharacter->getDescription());
    }

    public function testDetailCharacterPage(): void
    {
        $client = $this->createAuthenticatedClient();

        $character = $this->createTestCharacters()[0];

        $client->request('GET', sprintf('/admin/open-ai/character/%d', $character->getId()));
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('AI角色详情', $content);
        // 使用更通用的选择器，检查页面是否包含角色名称
        $this->assertStringContainsString($character->getName(), $content);
    }

    public function testCreateCharacterWithMissingRequiredFields(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/admin/open-ai/character/new', [
            'Character' => [
                'name' => '', // 空名称
                'systemPrompt' => '', // 空系统提示词
                'temperature' => 0.7,
                'maxTokens' => 2000,
                'topP' => 0.9,
                'presencePenalty' => 0.0,
                'frequencyPenalty' => 0.0,
                'valid' => true,
            ],
        ]);
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testCreateCharacterWithTooLongName(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/admin/open-ai/character/new', [
            'Character' => [
                'name' => str_repeat('a', 51), // 超过50字符限制
                'systemPrompt' => 'Valid system prompt',
                'temperature' => 0.7,
                'maxTokens' => 2000,
                'topP' => 0.9,
                'presencePenalty' => 0.0,
                'frequencyPenalty' => 0.0,
                'valid' => true,
            ],
        ]);
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testCreateCharacterWithInvalidTemperature(): void
    {
        $client = $this->createAuthenticatedClient();

        $client->request('POST', '/admin/open-ai/character/new', [
            'Character' => [
                'name' => 'Test Character',
                'systemPrompt' => 'Valid system prompt',
                'temperature' => '3.0', // 超过2.0的范围
                'maxTokens' => 2000,
                'topP' => 0.9,
                'presencePenalty' => 0.0,
                'frequencyPenalty' => 0.0,
                'valid' => true,
            ],
        ]);
        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testSearchCharactersByName(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->createTestCharacters();

        $client->request('GET', '/admin/open-ai/character?query=Assistant');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
        $this->assertStringContainsString('AI Assistant', $content);
    }

    public function testSearchCharactersByDescription(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->createTestCharacters();

        $client->request('GET', '/admin/open-ai/character?query=helpful');
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testFilterCharactersByValidStatus(): void
    {
        $client = $this->createAuthenticatedClient();

        $this->createTestCharacters();

        $client->request('GET', '/admin/open-ai/character', [
            'filters' => [
                'valid' => [
                    'comparison' => '=',
                    'value' => 1,
                ],
            ],
        ]);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testFilterCharactersByPreferredApiKey(): void
    {
        $client = $this->createAuthenticatedClient();

        $apiKey = $this->createTestApiKey();
        $this->createTestCharacters();

        $client->request('GET', '/admin/open-ai/character', [
            'filters' => [
                'preferredApiKey' => [
                    'comparison' => '=',
                    'value' => $apiKey->getId(),
                ],
            ],
        ]);
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

    /**
     * @return Character[]
     */
    private function createTestCharacters(): array
    {
        $em = self::getEntityManager();

        $apiKey = $this->createTestApiKey();

        $character1 = new Character();
        $character1->setName('AI Assistant');
        $character1->setDescription('A helpful AI assistant');
        $character1->setSystemPrompt('You are a helpful AI assistant.');
        $character1->setTemperature(0.7);
        $character1->setMaxTokens(2000);
        $character1->setTopP(0.9);
        $character1->setPresencePenalty(0.0);
        $character1->setFrequencyPenalty(0.0);
        $character1->setValid(true);
        $character1->setPreferredApiKey($apiKey);

        $character2 = new Character();
        $character2->setName('Code Helper');
        $character2->setDescription('A coding assistant');
        $character2->setSystemPrompt('You are a coding assistant specialized in PHP.');
        $character2->setTemperature(0.3);
        $character2->setMaxTokens(4000);
        $character2->setTopP(0.8);
        $character2->setPresencePenalty(0.1);
        $character2->setFrequencyPenalty(0.1);
        $character2->setValid(false);

        $em->persist($character1);
        $em->persist($character2);
        $em->flush();

        return [$character1, $character2];
    }
}
