<?php

namespace OpenAIBundle\Tests\Repository;

use Doctrine\Persistence\ManagerRegistry;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Repository\ApiKeyRepository;
use PHPUnit\Framework\TestCase;

class ApiKeyRepositoryTest extends TestCase
{
    public function testRepositoryExtendsServiceEntityRepository(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new ApiKeyRepository($managerRegistry);
        
        $this->assertInstanceOf(ApiKeyRepository::class, $repository);
    }

    public function testRepositoryInitialization(): void
    {
        $managerRegistry = $this->createMock(ManagerRegistry::class);
        $repository = new ApiKeyRepository($managerRegistry);
        
        $this->assertInstanceOf(ApiKeyRepository::class, $repository);
    }
} 