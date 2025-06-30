<?php

namespace OpenAIBundle\Tests\Integration\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Controller\ConversationChatController;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\ConversationRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ConversationChatControllerTest extends TestCase
{
    private ConversationChatController $controller;
    private ConversationRepository $conversationRepository;
    private ApiKeyRepository $apiKeyRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->conversationRepository = $this->createMock(ConversationRepository::class);
        $this->apiKeyRepository = $this->createMock(ApiKeyRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->controller = new ConversationChatController(
            $this->conversationRepository,
            $this->apiKeyRepository,
            $this->entityManager
        );

        // 设置容器以支持 json() 方法
        $container = new Container();
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $container->set('serializer', $serializer);
        $this->controller->setContainer($container);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(ConversationChatController::class, $this->controller);
    }

    public function testInvokeWithNonExistentConversation(): void
    {
        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $request = $this->createRequest([
            'message' => 'Hello',
            'apiKeyId' => '1'
        ]);

        $this->expectException(NotFoundHttpException::class);

        $this->controller->__invoke($request, 999);
    }

    public function testInvokeWithMissingApiKeyAndNoCharacterDefault(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->expects($this->once())
            ->method('getActor')
            ->willReturn(null);

        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($conversation);

        $this->apiKeyRepository->expects($this->never())
            ->method('find');

        $request = $this->createRequest([
            'message' => 'Hello'
        ]);

        $this->expectException(\OpenAIBundle\Exception\ConfigurationException::class);

        $this->controller->__invoke($request, 1);
    }

    public function testInvokeWithValidApiKey(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $apiKey = $this->createMock(ApiKey::class);

        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($conversation);

        $this->apiKeyRepository->expects($this->once())
            ->method('find')
            ->with('2')
            ->willReturn($apiKey);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Message::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $request = $this->createRequest([
            'message' => 'Hello',
            'apiKeyId' => '2'
        ]);

        $response = $this->controller->__invoke($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Message received', $responseData['message']);
        $this->assertArrayHasKey('messageId', $responseData);
    }

    public function testInvokeWithCharacterDefaultApiKey(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $character = $this->createMock(Character::class);
        $apiKey = $this->createMock(ApiKey::class);

        $character->expects($this->once())
            ->method('getPreferredApiKey')
            ->willReturn($apiKey);

        $conversation->expects($this->once())
            ->method('getActor')
            ->willReturn($character);

        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($conversation);

        $this->apiKeyRepository->expects($this->never())
            ->method('find');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Message::class));

        $this->entityManager->expects($this->once())
            ->method('flush');

        $request = $this->createRequest([
            'message' => 'Hello world'
        ]);

        $response = $this->controller->__invoke($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Message received', $responseData['message']);
        $this->assertArrayHasKey('messageId', $responseData);
    }

    private function createRequest(array $data): Request
    {
        $request = new Request();
        $request->initialize([], [], [], [], [], [], json_encode($data));
        $request->headers->set('Content-Type', 'application/json');
        
        return $request;
    }
}