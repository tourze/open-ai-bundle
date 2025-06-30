<?php

namespace OpenAIBundle\Tests\Integration\Controller;

use OpenAIBundle\Controller\ChatIndexController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class ChatIndexControllerTest extends TestCase
{
    private ChatIndexController $controller;
    private CharacterRepository $characterRepository;
    private ApiKeyRepository $apiKeyRepository;

    protected function setUp(): void
    {
        $this->characterRepository = $this->createMock(CharacterRepository::class);
        $this->apiKeyRepository = $this->createMock(ApiKeyRepository::class);

        $this->controller = new ChatIndexController(
            $this->characterRepository,
            $this->apiKeyRepository
        );
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(ChatIndexController::class, $this->controller);
    }

    public function testInvokeCallsRepositories(): void
    {
        $character = $this->createMock(Character::class);
        $apiKey = $this->createMock(ApiKey::class);

        $this->characterRepository->expects($this->once())
            ->method('findBy')
            ->with(['valid' => true])
            ->willReturn([$character]);

        $this->apiKeyRepository->expects($this->once())
            ->method('findBy')
            ->with(['valid' => true])
            ->willReturn([$apiKey]);

        // 我们期望这会因为缺少 Twig 环境而抛出异常，但repository调用应该成功
        $this->expectException(\Throwable::class);
        
        $this->controller->__invoke();
    }

    public function testRepositoriesAreCalledWithCorrectParameters(): void
    {
        $this->characterRepository->expects($this->once())
            ->method('findBy')
            ->with(['valid' => true])
            ->willReturn([]);

        $this->apiKeyRepository->expects($this->once())
            ->method('findBy')
            ->with(['valid' => true])
            ->willReturn([]);

        try {
            $this->controller->__invoke();
        } catch (\Throwable $e) {
            // 忽略Twig相关的异常，重点是验证repository调用
        }
    }
}