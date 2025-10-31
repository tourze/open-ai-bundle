<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Controller\Admin;

use OpenAIBundle\Controller\Admin\ConversationCrudController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Repository\ConversationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminControllerTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationCrudController::class)]
#[RunTestsInSeparateProcesses]
final class ConversationCrudControllerTest extends AbstractEasyAdminControllerTestCase
{
    /** @return ConversationCrudController */
    protected function getControllerService(): ConversationCrudController
    {
        return new ConversationCrudController();
    }

    /** @return iterable<string, array{string}> */
    public static function provideIndexPageHeaders(): iterable
    {
        yield 'ID' => ['ID'];
        yield '有效状态' => ['有效状态'];
        yield '对话标题' => ['对话标题'];
        yield '对话描述' => ['对话描述'];
        yield '使用模型' => ['使用模型'];
        yield '系统提示词' => ['系统提示词'];
        yield '对话角色' => ['对话角色'];
        yield '创建人' => ['创建人'];
        yield '更新人' => ['更新人'];
        yield '创建时间' => ['创建时间'];
        yield '更新时间' => ['更新时间'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideNewPageFields(): iterable
    {
        yield 'title' => ['title'];
        yield 'description' => ['description'];
        yield 'model' => ['model'];
        yield 'systemPrompt' => ['systemPrompt'];
        yield 'actor' => ['actor'];
    }

    /** @return iterable<string, array{string}> */
    public static function provideEditPageFields(): iterable
    {
        return self::provideNewPageFields();
    }

    public function testGetConversationIndexPageReturnsSuccessful(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestConversations();

        $client->request('GET', '/admin/open-ai/conversation');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testGetConversationNewPageReturnsSuccessful(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestCharacter();

        $client->request('GET', '/admin/open-ai/conversation/new');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('<form', $content);
        $this->assertStringContainsString('Conversation[title]', $content);
        $this->assertStringContainsString('Conversation[description]', $content);
    }

    public function testCreateConversationWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $character = $this->createTestCharacter();

        $client->request('POST', '/admin/open-ai/conversation/new', [
            'Conversation' => [
                'title' => 'Test Conversation',
                'description' => 'A test conversation for testing',
                'model' => 'gpt-3.5-turbo',
                'actor' => (string) $character->getId(),
                'valid' => true,
            ],
        ]);

        $this->assertTrue($client->getResponse()->isRedirection());

        /** @var ConversationRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\ConversationRepository');
        $conversation = $repository->findOneBy(['title' => 'Test Conversation']);
        $this->assertNotNull($conversation);
        $this->assertEquals('A test conversation for testing', $conversation->getDescription());
        $this->assertNotNull($conversation->getActor());
        $this->assertEquals($character->getId(), $conversation->getActor()->getId());
    }

    public function testEditConversationWithValidData(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');
        $em = self::getEntityManager();

        $conversation = $this->createTestConversations()[0];

        $client->request('POST', sprintf('/admin/open-ai/conversation/%d/edit', $conversation->getId()), [
            'Conversation' => [
                'title' => 'Updated Conversation',
                'description' => 'Updated description',
                'model' => $conversation->getModel(),
                'actor' => (string) $conversation->getActor()?->getId(),
                'valid' => $conversation->isValid(),
            ],
        ]);

        $this->assertTrue($client->getResponse()->isRedirection());

        $em->refresh($conversation);
        $this->assertEquals('Updated Conversation', $conversation->getTitle());
        $this->assertEquals('Updated description', $conversation->getDescription());
    }

    public function testDeleteConversation(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');
        $em = self::getEntityManager();

        $conversation = $this->createTestConversations()[0];
        $conversationId = $conversation->getId();

        $client->request('POST', sprintf('/admin/open-ai/conversation/%d/delete', $conversationId));

        $this->assertTrue($client->getResponse()->isRedirection());

        // 验证实体是否被删除（或软删除）
        $em->clear(); // 清除实体管理器缓存
        /** @var ConversationRepository $repository */
        $repository = self::getService('OpenAIBundle\Repository\ConversationRepository');
        $deletedConversation = $repository->find($conversationId);

        // 检查是否实际被删除或软删除（设置为无效）
        if (null !== $deletedConversation) {
            // 如果对象还存在，检查是否是软删除（设置为无效）
            $this->assertFalse($deletedConversation->isValid(), 'Conversation should be soft deleted (marked as invalid)');
        } else {
            // 如果对象被物理删除，这也是可以接受的
            $this->assertTrue(true, 'Conversation was physically deleted');
        }
    }

    public function testDetailConversationPage(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $conversation = $this->createTestConversations()[0];

        $client->request('GET', sprintf('/admin/open-ai/conversation/%d', $conversation->getId()));

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('对话详情', $content);
        $this->assertStringContainsString($conversation->getTitle(), $content);
    }

    public function testCreateConversationWithMissingRequiredFields(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $character = $this->createTestCharacter();

        // 测试空的 title 字段（应该触发NotBlank验证）
        $client->request('POST', '/admin/open-ai/conversation/new', [
            'Conversation' => [
                'title' => '',
                'description' => 'Valid description',
                'model' => 'gpt-3.5-turbo',
                'actor' => (string) $character->getId(),
                'valid' => true,
            ],
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testCreateConversationWithTooLongTitle(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $character = $this->createTestCharacter();

        $client->request('POST', '/admin/open-ai/conversation/new', [
            'Conversation' => [
                'title' => str_repeat('a', 256), // 超过255字符限制
                'description' => 'Valid description',
                'model' => 'gpt-3.5-turbo',
                'actor' => (string) $character->getId(),
                'valid' => true,
            ],
        ]);

        $this->assertEquals(422, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('invalid-feedback', $content);
    }

    public function testSearchConversationsByTitle(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestConversations();

        $client->request('GET', '/admin/open-ai/conversation?query=Chat');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
        $this->assertStringContainsString('AI Chat Session', $content);
    }

    public function testSearchConversationsByDescription(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $this->createTestConversations();

        $client->request('GET', '/admin/open-ai/conversation?query=coding');

        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $content = $client->getResponse()->getContent();
        self::assertIsString($content);
        $this->assertStringContainsString('datagrid', $content);
    }

    public function testFilterConversationsByActor(): void
    {
        $client = self::createClientWithDatabase();
        $this->createAdminUser('admin@test.com', 'admin123');
        $this->loginAsAdmin($client, 'admin@test.com', 'admin123');

        $character = $this->createTestCharacter();
        $this->createTestConversations();

        $client->request('GET', sprintf('/admin/open-ai/conversation?filters[actor][value]=%d&filters[actor][comparison]=equal', $character->getId()));

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

    /**
     * @return Conversation[]
     */
    private function createTestConversations(): array
    {
        $em = self::getEntityManager();

        $character = $this->createTestCharacter();

        $conversation1 = new Conversation();
        $conversation1->setTitle('AI Chat Session');
        $conversation1->setDescription('A general chat session with AI');
        $conversation1->setActor($character);

        $conversation2 = new Conversation();
        $conversation2->setTitle('Coding Help');
        $conversation2->setDescription('Help with coding problems');
        $conversation2->setActor($character);

        $em->persist($conversation1);
        $em->persist($conversation2);
        $em->flush();

        return [$conversation1, $conversation2];
    }

    public function testAccessDeniedForUnauthenticatedUser(): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/open-ai/conversation');
    }

    public function testAccessDeniedForNormalUser(): void
    {
        $client = self::createClientWithDatabase();
        $this->loginAsUser($client);

        $this->expectException(AccessDeniedException::class);
        $this->expectExceptionMessage('Access Denied. The user doesn\'t have ROLE_ADMIN.');

        $client->request('GET', '/admin/open-ai/conversation');
    }
}
