<?php

namespace OpenAIBundle\Tests\Integration\Controller;

use OpenAIBundle\Controller\ChatCreateController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\ConversationService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ChatCreateControllerTest extends TestCase
{
    private ChatCreateController $controller;
    private CharacterRepository $characterRepository;
    private ApiKeyRepository $apiKeyRepository;
    private ConversationService $conversationService;

    protected function setUp(): void
    {
        $this->characterRepository = $this->createMock(CharacterRepository::class);
        $this->apiKeyRepository = $this->createMock(ApiKeyRepository::class);
        $this->conversationService = $this->createMock(ConversationService::class);

        $this->controller = new ChatCreateController(
            $this->characterRepository,
            $this->apiKeyRepository,
            $this->conversationService
        );

        // 设置容器以支持 json() 方法
        $container = new Container();
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $container->set('serializer', $serializer);
        $this->controller->setContainer($container);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(ChatCreateController::class, $this->controller);
    }

    public function testInvokeWithMissingCharacterId(): void
    {
        $request = $this->createRequest([
            'apiKeyId' => '1'
        ]);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with(null)
            ->willReturn(null);

        $this->expectException(\TypeError::class);

        $this->controller->__invoke($request);
    }

    public function testInvokeWithMissingApiKeyId(): void
    {
        $character = $this->createMock(Character::class);
        
        $request = $this->createRequest([
            'characterId' => '1'
        ]);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($character);

        $this->apiKeyRepository->expects($this->once())
            ->method('find')
            ->with(null)
            ->willReturn(null);

        $this->expectException(\TypeError::class);

        $this->controller->__invoke($request);
    }

    public function testInvokeWithInvalidCharacterId(): void
    {
        $request = $this->createRequest([
            'characterId' => '999',
            'apiKeyId' => '1'
        ]);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('999')
            ->willReturn(null);

        $this->expectException(\OpenAIBundle\Exception\ConfigurationException::class);

        $this->controller->__invoke($request);
    }

    public function testInvokeWithInvalidApiKeyId(): void
    {
        $character = $this->createMock(Character::class);
        
        $request = $this->createRequest([
            'characterId' => '1',
            'apiKeyId' => '999'
        ]);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($character);

        $this->apiKeyRepository->expects($this->once())
            ->method('find')
            ->with('999')
            ->willReturn(null);

        $this->expectException(\OpenAIBundle\Exception\ConfigurationException::class);

        $this->controller->__invoke($request);
    }

    public function testInvokeWithValidData(): void
    {
        $character = $this->createMock(Character::class);
        $apiKey = $this->createMock(ApiKey::class);
        $conversation = $this->createMock(Conversation::class);
        
        $conversation->expects($this->once())
            ->method('getId')
            ->willReturn('conv-123');
        
        $conversation->expects($this->once())
            ->method('getDescription')
            ->willReturn('Test conversation');

        $request = $this->createRequest([
            'characterId' => '1',
            'apiKeyId' => '2'
        ]);

        $this->characterRepository->expects($this->once())
            ->method('find')
            ->with('1')
            ->willReturn($character);

        $this->apiKeyRepository->expects($this->once())
            ->method('find')
            ->with('2')
            ->willReturn($apiKey);

        $this->conversationService->expects($this->once())
            ->method('initConversation')
            ->with($character, $apiKey)
            ->willReturn($conversation);

        $response = $this->controller->__invoke($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('conv-123', $responseData['conversationId']);
        $this->assertEquals('Test conversation', $responseData['description']);
    }

    private function createRequest(array $data): Request
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        
        return $request;
    }
}