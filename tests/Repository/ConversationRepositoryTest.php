<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Repository;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Repository\ConversationRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ConversationRepository::class)]
#[RunTestsInSeparateProcesses]
final class ConversationRepositoryTest extends AbstractRepositoryTestCase
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

        $entity = new Conversation();
        $entity->setTitle('Test Conversation ' . uniqid());
        $entity->setValid(true);
        $entity->setActor($character);

        return $entity;
    }

    protected function getRepository(): ConversationRepository
    {
        return self::getService(ConversationRepository::class);
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

    public function testRepositoryService(): void
    {
        $this->assertInstanceOf(ConversationRepository::class, $this->getRepository());
    }

    public function testFindLatestConversations(): void
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

        $conversation3 = new Conversation();
        $conversation3->setTitle('Third Conversation');
        $conversation3->setValid(true);
        $conversation3->setActor($character);

        self::getEntityManager()->persist($conversation1);
        self::getEntityManager()->flush();

        sleep(1);

        self::getEntityManager()->persist($conversation2);
        self::getEntityManager()->flush();

        sleep(1);

        self::getEntityManager()->persist($conversation3);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findLatestConversations(2);

        $this->assertCount(2, $result);
        $this->assertSame('Third Conversation', $result[0]->getTitle());
        $this->assertSame('Second Conversation', $result[1]->getTitle());
    }

    public function testFindLatestConversationsWithLimit(): void
    {
        $character = $this->createTestCharacter();

        for ($i = 1; $i <= 5; ++$i) {
            $conversation = new Conversation();
            $conversation->setTitle("Conversation {$i}");
            $conversation->setValid(true);
            $conversation->setActor($character);
            self::getEntityManager()->persist($conversation);
        }
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findLatestConversations(3);

        $this->assertCount(3, $result);
    }

    public function testFindByTitleLike(): void
    {
        $character = $this->createTestCharacter();

        $conversation1 = new Conversation();
        $conversation1->setTitle('AI Chat Session');
        $conversation1->setValid(true);
        $conversation1->setActor($character);

        $conversation2 = new Conversation();
        $conversation2->setTitle('Programming Help');
        $conversation2->setValid(true);
        $conversation2->setActor($character);

        $conversation3 = new Conversation();
        $conversation3->setTitle('AI Assistant Chat');
        $conversation3->setValid(true);
        $conversation3->setActor($character);

        self::getEntityManager()->persist($conversation1);
        self::getEntityManager()->persist($conversation2);
        self::getEntityManager()->persist($conversation3);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByTitleLike('AI');

        $this->assertCount(2, $result);
        $this->assertStringContainsString('AI', $result[0]->getTitle());
        $this->assertStringContainsString('AI', $result[1]->getTitle());
    }

    public function testFindByTitleLikeWithNoMatches(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Programming Help');
        $conversation->setValid(true);
        $conversation->setActor($character);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByTitleLike('NonExistent');

        $this->assertCount(0, $result);
    }

    public function testFindByTitleLikeOrderedByCreatedAt(): void
    {
        $character = $this->createTestCharacter();

        $conversation1 = new Conversation();
        $conversation1->setTitle('Chat Session 1');
        $conversation1->setValid(true);
        $conversation1->setActor($character);

        $conversation2 = new Conversation();
        $conversation2->setTitle('Chat Session 2');
        $conversation2->setValid(true);
        $conversation2->setActor($character);

        self::getEntityManager()->persist($conversation1);
        self::getEntityManager()->flush();

        sleep(1);

        self::getEntityManager()->persist($conversation2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findByTitleLike('Chat Session');

        $this->assertCount(2, $result);
        $this->assertSame('Chat Session 2', $result[0]->getTitle());
        $this->assertSame('Chat Session 1', $result[1]->getTitle());
    }

    public function testFindOneByOrderByLogic(): void
    {
        $character = $this->createTestCharacter();

        $conversation1 = new Conversation();
        $conversation1->setTitle('Duplicate Status 1');
        $conversation1->setValid(true);
        $conversation1->setActor($character);

        self::getEntityManager()->persist($conversation1);
        self::getEntityManager()->flush();

        $conversation2 = new Conversation();
        $conversation2->setTitle('Duplicate Status 2');
        $conversation2->setValid(true);
        $conversation2->setActor($character);

        self::getEntityManager()->persist($conversation2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findOneBy(['valid' => true], ['title' => 'ASC']);

        $this->assertInstanceOf(Conversation::class, $result);
        $this->assertSame('Duplicate Status 1', $result->getTitle());
    }

    public function testSaveAndRemoveMethods(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Save Remove Test');
        $conversation->setValid(true);
        $conversation->setActor($character);

        // Test save
        $this->getRepository()->save($conversation);

        // Verify saved
        $this->assertNotNull($conversation->getId());
        $savedConversation = $this->getRepository()->find($conversation->getId());
        $this->assertInstanceOf(Conversation::class, $savedConversation);
        $this->assertSame('Save Remove Test', $savedConversation->getTitle());

        // Test remove
        $conversationId = $conversation->getId();
        $this->getRepository()->remove($conversation);
        $removedConversation = $this->getRepository()->find($conversationId);
        $this->assertNull($removedConversation);
    }

    public function testFindByNullableFields(): void
    {
        $character = $this->createTestCharacter();

        $conversationWithoutDescription = new Conversation();
        $conversationWithoutDescription->setTitle('No Description Conversation');
        $conversationWithoutDescription->setValid(true);
        $conversationWithoutDescription->setActor($character);

        self::getEntityManager()->persist($conversationWithoutDescription);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->createQueryBuilder('c')
            ->where('c.description IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertCount(1, $result);
        $this->assertIsArray($result);
        /** @var Conversation $conversation */
        $conversation = $result[0];
        $this->assertSame('No Description Conversation', $conversation->getTitle());
    }

    public function testFindByActorRelation(): void
    {
        $character1 = $this->createTestCharacter();

        $character2 = new Character();
        $character2->setName('Second Character');
        $character2->setSystemPrompt('You are the second character.');
        $character2->setValid(true);
        self::getEntityManager()->persist($character2);
        self::getEntityManager()->flush();

        $conversation1 = new Conversation();
        $conversation1->setTitle('Character 1 Conversation');
        $conversation1->setValid(true);
        $conversation1->setActor($character1);

        $conversation2 = new Conversation();
        $conversation2->setTitle('Character 2 Conversation');
        $conversation2->setValid(true);
        $conversation2->setActor($character2);

        self::getEntityManager()->persist($conversation1);
        self::getEntityManager()->persist($conversation2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findBy(['actor' => $character1]);

        $this->assertCount(1, $result);
        $this->assertSame('Character 1 Conversation', $result[0]->getTitle());
        $actor = $result[0]->getActor();
        $this->assertInstanceOf(Character::class, $actor);
        $this->assertSame($character1->getId(), $actor->getId());
    }

    // PHPStan 规则要求的关联字段测试方法
    public function testFindOneByAssociationActorShouldReturnMatchingEntity(): void
    {
        $character = $this->createTestCharacter();

        $conversation = new Conversation();
        $conversation->setTitle('Association Test Conversation');
        $conversation->setValid(true);
        $conversation->setActor($character);

        self::getEntityManager()->persist($conversation);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findOneBy(['actor' => $character]);

        $this->assertInstanceOf(Conversation::class, $result);
        $actor = $result->getActor();
        $this->assertInstanceOf(Character::class, $actor);
        $this->assertSame($character->getId(), $actor->getId());
    }

    public function testCountByAssociationActorShouldReturnCorrectNumber(): void
    {
        $character1 = $this->createTestCharacter();

        $character2 = new Character();
        $character2->setName('Second Character');
        $character2->setSystemPrompt('You are the second character.');
        $character2->setValid(true);
        self::getEntityManager()->persist($character2);
        self::getEntityManager()->flush();

        // 为 character1 创建 3 个对话
        for ($i = 1; $i <= 3; ++$i) {
            $conversation = new Conversation();
            $conversation->setTitle("Character 1 Conversation {$i}");
            $conversation->setValid(true);
            $conversation->setActor($character1);
            self::getEntityManager()->persist($conversation);
        }

        // 为 character2 创建 2 个对话
        for ($i = 1; $i <= 2; ++$i) {
            $conversation = new Conversation();
            $conversation->setTitle("Character 2 Conversation {$i}");
            $conversation->setValid(true);
            $conversation->setActor($character2);
            self::getEntityManager()->persist($conversation);
        }

        self::getEntityManager()->flush();

        $count1 = $this->getRepository()->count(['actor' => $character1]);
        $count2 = $this->getRepository()->count(['actor' => $character2]);

        $this->assertSame(3, $count1);
        $this->assertSame(2, $count2);
    }

    // PHPStan 规则要求的可空字段测试方法

    // PHPStan 规则要求的排序逻辑测试方法
}
