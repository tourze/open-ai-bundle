<?php

declare(strict_types=1);

namespace OpenAIBundle\Tests\Repository;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Enum\ContextLength;
use OpenAIBundle\Repository\ApiKeyRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ApiKeyRepository::class)]
#[RunTestsInSeparateProcesses]
final class ApiKeyRepositoryTest extends AbstractRepositoryTestCase
{
    protected function onSetUp(): void
    {
        // Repository 测试类通常不需要特殊的设置
        $this->clearEntityCache();
    }

    private function clearEntityCache(): void
    {
        // 清除一级缓存中可能存在的实体，确保数据库连接测试的一致性
        $em = self::getEntityManager();
        $unitOfWork = $em->getUnitOfWork();

        // 清除所有实体缓存以确保测试隔离性
        $unitOfWork->clear();
    }

    protected function createNewEntity(): object
    {
        $entity = new ApiKey();
        $entity->setValid(true);
        $entity->setTitle('Test API Key ' . uniqid());
        $entity->setApiKey('sk-test-' . uniqid());
        $entity->setModel('gpt-3.5-turbo');
        $entity->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $entity->setContextLength(ContextLength::K_4);

        return $entity;
    }

    protected function getRepository(): ApiKeyRepository
    {
        return self::getService(ApiKeyRepository::class);
    }

    public function testRepositoryService(): void
    {
        $this->assertInstanceOf(ApiKeyRepository::class, $this->getRepository());
    }

    public function testFindAllValid(): void
    {
        $validApiKey = new ApiKey();
        $validApiKey->setValid(true);
        $validApiKey->setTitle('Valid Key');
        $validApiKey->setApiKey('sk-test-valid');
        $validApiKey->setModel('gpt-3.5-turbo');
        $validApiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $validApiKey->setContextLength(ContextLength::K_4);

        $invalidApiKey = new ApiKey();
        $invalidApiKey->setValid(false);
        $invalidApiKey->setTitle('Invalid Key');
        $invalidApiKey->setApiKey('sk-test-invalid');
        $invalidApiKey->setModel('gpt-3.5-turbo');
        $invalidApiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $invalidApiKey->setContextLength(ContextLength::K_4);

        $em = self::getEntityManager();
        $em->persist($validApiKey);
        $em->persist($invalidApiKey);
        $em->flush();

        $result = $this->getRepository()->findAllValid();

        $this->assertGreaterThanOrEqual(1, count($result));

        // 确保所有返回的keys都是有效的
        foreach ($result as $apiKey) {
            $this->assertTrue($apiKey->isValid());
        }

        // 确保我们创建的有效key在结果中
        $validKeyFound = false;
        foreach ($result as $apiKey) {
            if ('Valid Key' === $apiKey->getTitle()) {
                $validKeyFound = true;
                break;
            }
        }
        $this->assertTrue($validKeyFound, 'Our created valid key should be in the result');
    }

    public function testFindAllValidWithNoValidKeys(): void
    {
        // 清空所有现有的API keys
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . ApiKey::class)->execute();

        $invalidApiKey = new ApiKey();
        $invalidApiKey->setValid(false);
        $invalidApiKey->setTitle('Invalid Key');
        $invalidApiKey->setApiKey('sk-test-invalid');
        $invalidApiKey->setModel('gpt-3.5-turbo');
        $invalidApiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $invalidApiKey->setContextLength(ContextLength::K_4);

        $em->persist($invalidApiKey);
        $em->flush();

        $result = $this->getRepository()->findAllValid();

        $this->assertCount(0, $result);
    }

    public function testFindAllValidWithMultipleValidKeys(): void
    {
        // 清空所有现有的API keys
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . ApiKey::class)->execute();

        $validApiKey1 = new ApiKey();
        $validApiKey1->setValid(true);
        $validApiKey1->setTitle('Valid Key 1');
        $validApiKey1->setApiKey('sk-test-valid-1');
        $validApiKey1->setModel('gpt-3.5-turbo');
        $validApiKey1->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $validApiKey1->setContextLength(ContextLength::K_4);

        $validApiKey2 = new ApiKey();
        $validApiKey2->setValid(true);
        $validApiKey2->setTitle('Valid Key 2');
        $validApiKey2->setApiKey('sk-test-valid-2');
        $validApiKey2->setModel('gpt-4');
        $validApiKey2->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $validApiKey2->setContextLength(ContextLength::K_8);

        $em->persist($validApiKey1);
        $em->persist($validApiKey2);
        $em->flush();

        $result = $this->getRepository()->findAllValid();

        $this->assertCount(2, $result);
        $this->assertTrue($result[0]->isValid());
        $this->assertTrue($result[1]->isValid());
    }

    public function testFindOneByOrderByLogic(): void
    {
        // 清空所有现有的API keys以确保测试隔离
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . ApiKey::class)->execute();

        $apiKey1 = new ApiKey();
        $apiKey1->setValid(true);
        $apiKey1->setTitle('Duplicate Model Key');
        $apiKey1->setApiKey('sk-test-first');
        $apiKey1->setModel('gpt-3.5-turbo');
        $apiKey1->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey1->setContextLength(ContextLength::K_4);

        $apiKey2 = new ApiKey();
        $apiKey2->setValid(true);
        $apiKey2->setTitle('Another Duplicate Model Key');
        $apiKey2->setApiKey('sk-test-second');
        $apiKey2->setModel('gpt-3.5-turbo');
        $apiKey2->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey2->setContextLength(ContextLength::K_4);

        $em->persist($apiKey1);
        $em->flush();

        sleep(1);

        $em->persist($apiKey2);
        $em->flush();

        $result = $this->getRepository()->findOneBy(['model' => 'gpt-3.5-turbo'], ['id' => 'ASC']);

        $this->assertInstanceOf(ApiKey::class, $result);
        $this->assertSame($apiKey1->getId(), $result->getId());
    }

    public function testSaveAndRemoveMethods(): void
    {
        $apiKey = new ApiKey();
        $apiKey->setValid(true);
        $apiKey->setTitle('Save Remove Test');
        $apiKey->setApiKey('sk-save-remove-test');
        $apiKey->setModel('gpt-3.5-turbo');
        $apiKey->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKey->setContextLength(ContextLength::K_4);

        // Test save
        $this->getRepository()->save($apiKey);

        // Verify saved
        $this->assertNotNull($apiKey->getId());
        $savedKey = $this->getRepository()->find($apiKey->getId());
        $this->assertInstanceOf(ApiKey::class, $savedKey);
        $this->assertSame('Save Remove Test', $savedKey->getTitle());

        // Test remove
        $keyId = $apiKey->getId();
        $this->getRepository()->remove($apiKey);
        $removedKey = $this->getRepository()->find($keyId);
        $this->assertNull($removedKey);
    }

    public function testFindByNullableFields(): void
    {
        $apiKeyWithoutFunction = new ApiKey();
        $apiKeyWithoutFunction->setValid(true);
        $apiKeyWithoutFunction->setTitle('No Function Key');
        $apiKeyWithoutFunction->setApiKey('sk-no-function');
        $apiKeyWithoutFunction->setModel('gpt-3.5-turbo');
        $apiKeyWithoutFunction->setChatCompletionUrl('https://api.openai.com/v1/chat/completions');
        $apiKeyWithoutFunction->setContextLength(ContextLength::K_4);
        $apiKeyWithoutFunction->setFunctionCalling(null);

        $em = self::getEntityManager();
        $em->persist($apiKeyWithoutFunction);
        $em->flush();

        $result = $this->getRepository()->createQueryBuilder('ak')
            ->where('ak.functionCalling IS NULL')
            ->getQuery()
            ->getResult()
        ;

        $this->assertCount(1, $result);
        $this->assertSame('No Function Key', $result[0]->getTitle());
    }

    // PHPStan 规则要求的可空字段测试方法

    // PHPStan 规则要求的排序逻辑测试方法
}
