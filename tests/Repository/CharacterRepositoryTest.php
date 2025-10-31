<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Repository;

use OpenAIBundle\Entity\Character;
use OpenAIBundle\Repository\CharacterRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(CharacterRepository::class)]
#[RunTestsInSeparateProcesses]
final class CharacterRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
    }

    protected function createNewEntity(): object
    {
        $entity = new Character();
        $entity->setName('Test Character ' . uniqid());
        $entity->setSystemPrompt('You are a helpful assistant.');
        $entity->setTemperature(1.0);
        $entity->setTopP(0.7);
        $entity->setMaxTokens(2000);
        $entity->setPresencePenalty(0.0);
        $entity->setFrequencyPenalty(0.0);
        $entity->setValid(true);

        return $entity;
    }

    protected function getRepository(): CharacterRepository
    {
        return self::getService(CharacterRepository::class);
    }

    public function testRepositoryService(): void
    {
        $this->assertInstanceOf(CharacterRepository::class, $this->getRepository());
    }

    public function testFindAllActive(): void
    {
        // 清空所有现有的Character数据以确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . Character::class)->execute();

        $activeCharacter = new Character();
        $activeCharacter->setName('Active Character');
        $activeCharacter->setSystemPrompt('You are an active assistant');
        $activeCharacter->setValid(true);

        $inactiveCharacter = new Character();
        $inactiveCharacter->setName('Inactive Character');
        $inactiveCharacter->setSystemPrompt('You are an inactive assistant');
        $inactiveCharacter->setValid(false);

        $em->persist($activeCharacter);
        $em->persist($inactiveCharacter);
        $em->flush();

        $result = $this->getRepository()->findAllActive();

        $this->assertCount(1, $result);
        $this->assertTrue($result[0]->isValid());
        $this->assertSame('Active Character', $result[0]->getName());
    }

    public function testFindAllActiveWithNoActiveCharacters(): void
    {
        // 清空所有现有的Character数据以确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . Character::class)->execute();

        $inactiveCharacter = new Character();
        $inactiveCharacter->setName('Inactive Character');
        $inactiveCharacter->setSystemPrompt('You are an inactive assistant');
        $inactiveCharacter->setValid(false);

        $em->persist($inactiveCharacter);
        $em->flush();

        $result = $this->getRepository()->findAllActive();

        $this->assertCount(0, $result);
    }

    public function testFindOneByName(): void
    {
        $character = new Character();
        $character->setName('Test Character');
        $character->setSystemPrompt('You are a test assistant');
        $character->setValid(true);

        self::getEntityManager()->persist($character);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findOneByName('Test Character');

        $this->assertInstanceOf(Character::class, $result);
        $this->assertSame('Test Character', $result->getName());
    }

    public function testFindOneByNameReturnsNull(): void
    {
        $result = $this->getRepository()->findOneByName('Non-existent Character');

        $this->assertNull($result);
    }

    public function testFindOneByNameWithMultipleCharacters(): void
    {
        $character1 = new Character();
        $character1->setName('Character One');
        $character1->setSystemPrompt('You are character one');
        $character1->setValid(true);

        $character2 = new Character();
        $character2->setName('Character Two');
        $character2->setSystemPrompt('You are character two');
        $character2->setValid(true);

        self::getEntityManager()->persist($character1);
        self::getEntityManager()->persist($character2);
        self::getEntityManager()->flush();

        $result = $this->getRepository()->findOneByName('Character One');

        $this->assertInstanceOf(Character::class, $result);
        $this->assertSame('Character One', $result->getName());
    }

    public function testFindOneByOrderByLogic(): void
    {
        // 清空所有现有的Character数据以确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . Character::class)->execute();

        $character1 = new Character();
        $character1->setName('Duplicate Status');
        $character1->setSystemPrompt('You are the first');
        $character1->setValid(true);

        $character2 = new Character();
        $character2->setName('Another Duplicate Status');
        $character2->setSystemPrompt('You are the second');
        $character2->setValid(true);

        $em->persist($character1);
        $em->flush();

        sleep(1);

        $em->persist($character2);
        $em->flush();

        $result = $this->getRepository()->findOneBy(['valid' => true], ['id' => 'ASC']);

        $this->assertInstanceOf(Character::class, $result);
        $this->assertSame($character1->getId(), $result->getId());
    }

    public function testSaveAndRemoveMethods(): void
    {
        $character = new Character();
        $character->setName('Save Remove Test');
        $character->setSystemPrompt('You are a save/remove test');
        $character->setValid(true);

        // Test save
        $this->getRepository()->save($character);

        // Verify saved
        $this->assertNotNull($character->getId());
        $savedCharacter = $this->getRepository()->find($character->getId());
        $this->assertInstanceOf(Character::class, $savedCharacter);
        $this->assertSame('Save Remove Test', $savedCharacter->getName());

        // Test remove
        $characterId = $character->getId();
        $this->getRepository()->remove($character);
        $removedCharacter = $this->getRepository()->find($characterId);
        $this->assertNull($removedCharacter);
    }

    public function testFindByNullableFields(): void
    {
        // 清空所有现有的Character记录以确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . Character::class)->execute();

        $characterWithoutAvatar = new Character();
        $characterWithoutAvatar->setName('No Avatar Character');
        $characterWithoutAvatar->setSystemPrompt('You are a character without avatar');
        $characterWithoutAvatar->setValid(true);

        $em->persist($characterWithoutAvatar);
        $em->flush();

        $result = $this->getRepository()->createQueryBuilder('c')
            ->where('c.avatar IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertCount(1, $result);
        $this->assertSame('No Avatar Character', $result[0]->getName());
    }
}
