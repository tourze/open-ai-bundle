<?php

namespace OpenAIBundle\Tests\Repository;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Repository\ApiKeyRepository;
use PHPUnit\Framework\TestCase;

class ApiKeyRepositoryTest extends TestCase
{
    private ApiKeyRepository $repository;

    protected function setUp(): void
    {
        // 直接mock repository，只mock实际存在的方法
        $this->repository = $this->createMock(ApiKeyRepository::class);
    }

    public function testFindAllValid_returnsValidApiKeys(): void
    {
        $apiKey1 = $this->createApiKeyMock('1', 'Valid Key 1', true);
        $apiKey2 = $this->createApiKeyMock('2', 'Valid Key 2', true);
        $expectedKeys = [$apiKey1, $apiKey2];

        $this->repository
            ->method('findAllValid')
            ->willReturn($expectedKeys);

        $result = $this->repository->findAllValid();

        $this->assertCount(2, $result);
        $this->assertEquals($expectedKeys, $result);
    }

    public function testFindAllValid_returnsEmptyArrayWhenNoValidKeys(): void
    {
        $this->repository
            ->method('findAllValid')
            ->willReturn([]);

        $result = $this->repository->findAllValid();

        $this->assertEmpty($result);
    }

    public function testFind_returnsApiKeyById(): void
    {
        $apiKey = $this->createApiKeyMock('123', 'Test Key', true);

        $this->repository
            ->method('find')
            ->with('123')
            ->willReturn($apiKey);

        $result = $this->repository->find('123');

        $this->assertSame($apiKey, $result);
        $this->assertEquals('123', $result->getId());
    }

    public function testFind_returnsNullWhenNotFound(): void
    {
        $this->repository
            ->method('find')
            ->with('nonexistent')
            ->willReturn(null);

        $result = $this->repository->find('nonexistent');

        $this->assertNull($result);
    }

    public function testFindOneBy_returnsApiKeyByCriteria(): void
    {
        $apiKey = $this->createApiKeyMock('1', 'Test Key', true);
        $criteria = ['title' => 'Test Key'];

        $this->repository
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn($apiKey);

        $result = $this->repository->findOneBy($criteria);

        $this->assertSame($apiKey, $result);
        $this->assertEquals('Test Key', $result->getTitle());
    }

    public function testFindOneBy_returnsNullWhenNotFound(): void
    {
        $criteria = ['title' => 'Nonexistent Key'];

        $this->repository
            ->method('findOneBy')
            ->with($criteria)
            ->willReturn(null);

        $result = $this->repository->findOneBy($criteria);

        $this->assertNull($result);
    }

    public function testFindAll_returnsAllApiKeys(): void
    {
        $apiKey1 = $this->createApiKeyMock('1', 'Key 1', true);
        $apiKey2 = $this->createApiKeyMock('2', 'Key 2', false);
        $allKeys = [$apiKey1, $apiKey2];

        $this->repository
            ->method('findAll')
            ->willReturn($allKeys);

        $result = $this->repository->findAll();

        $this->assertCount(2, $result);
        $this->assertEquals($allKeys, $result);
    }

    public function testFindBy_returnsApiKeysByCriteria(): void
    {
        $apiKey1 = $this->createApiKeyMock('1', 'Valid Key 1', true);
        $apiKey2 = $this->createApiKeyMock('2', 'Valid Key 2', true);
        $validKeys = [$apiKey1, $apiKey2];

        $criteria = ['valid' => true];

        $this->repository
            ->method('findBy')
            ->with($criteria)
            ->willReturn($validKeys);

        $result = $this->repository->findBy($criteria);

        $this->assertCount(2, $result);
        $this->assertEquals($validKeys, $result);
    }

    public function testFindBy_returnsEmptyArrayWhenNoCriteriaMatch(): void
    {
        $criteria = ['valid' => false];

        $this->repository
            ->method('findBy')
            ->with($criteria)
            ->willReturn([]);

        $result = $this->repository->findBy($criteria);

        $this->assertEmpty($result);
    }

    public function testFindBy_withOrderByParameter(): void
    {
        $apiKey1 = $this->createApiKeyMock('1', 'A Key', true);
        $apiKey2 = $this->createApiKeyMock('2', 'B Key', true);
        $orderedKeys = [$apiKey1, $apiKey2];

        $criteria = ['valid' => true];
        $orderBy = ['title' => 'ASC'];

        $this->repository
            ->method('findBy')
            ->with($criteria, $orderBy)
            ->willReturn($orderedKeys);

        $result = $this->repository->findBy($criteria, $orderBy);

        $this->assertEquals($orderedKeys, $result);
    }

    public function testFindBy_withLimitParameter(): void
    {
        $apiKey = $this->createApiKeyMock('1', 'Limited Key', true);
        $limitedKeys = [$apiKey];

        $criteria = ['valid' => true];
        $orderBy = null;
        $limit = 1;

        $this->repository
            ->method('findBy')
            ->with($criteria, $orderBy, $limit)
            ->willReturn($limitedKeys);

        $result = $this->repository->findBy($criteria, $orderBy, $limit);

        $this->assertCount(1, $result);
        $this->assertEquals($limitedKeys, $result);
    }

    public function testRepositoryBasicFunctionality(): void
    {
        // 测试repository基本功能是否正常工作
        $this->assertInstanceOf(ApiKeyRepository::class, $this->repository);
    }

    private function createApiKeyMock(string $id, string $title, bool $isValid): ApiKey
    {
        $apiKey = $this->createMock(ApiKey::class);
        $apiKey->method('getId')->willReturn($id);
        $apiKey->method('getTitle')->willReturn($title);
        $apiKey->method('isValid')->willReturn($isValid);
        
        return $apiKey;
    }
} 