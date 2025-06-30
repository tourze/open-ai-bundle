<?php

namespace OpenAIBundle\Tests\Integration\Controller;

use OpenAIBundle\Controller\ConversationMessagesController;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\ConversationRepository;
use OpenAIBundle\Repository\MessageRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

class ConversationMessagesControllerTest extends TestCase
{
    private ConversationMessagesController $controller;
    private ConversationRepository $conversationRepository;
    private MessageRepository $messageRepository;

    protected function setUp(): void
    {
        $this->conversationRepository = $this->createMock(ConversationRepository::class);
        $this->messageRepository = $this->createMock(MessageRepository::class);

        $this->controller = new ConversationMessagesController(
            $this->conversationRepository,
            $this->messageRepository
        );

        // 设置容器以支持 json() 方法
        $container = new Container();
        $serializer = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $container->set('serializer', $serializer);
        $this->controller->setContainer($container);
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(ConversationMessagesController::class, $this->controller);
    }

    public function testInvokeWithNonExistentConversation(): void
    {
        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $request = new Request();
        $response = $this->controller->__invoke($request, 999);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals('Conversation not found', $responseData['error']);
    }

    public function testInvokeWithExistingConversationNoMessages(): void
    {
        $conversation = $this->createMock(Conversation::class);

        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($conversation);

        $this->messageRepository->expects($this->once())
            ->method('findByConversation')
            ->with($conversation)
            ->willReturn([]);

        $request = new Request();
        $response = $this->controller->__invoke($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertEquals([], $responseData['messages']);
        $this->assertEquals(0, $responseData['total']);
    }

    public function testInvokeWithExistingConversationWithMessages(): void
    {
        $conversation = $this->createMock(Conversation::class);
        
        $message1 = $this->createMock(Message::class);
        $message1->expects($this->once())->method('getId')->willReturn('msg-1');
        $message1->expects($this->once())->method('getRole')->willReturn(RoleEnum::user);
        $message1->expects($this->once())->method('getContent')->willReturn('Hello');
        $message1->expects($this->once())->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-01-01 10:00:00'));
        $message1->expects($this->once())->method('getToolCalls')->willReturn(null);

        $message2 = $this->createMock(Message::class);
        $message2->expects($this->once())->method('getId')->willReturn('msg-2');
        $message2->expects($this->once())->method('getRole')->willReturn(RoleEnum::assistant);
        $message2->expects($this->once())->method('getContent')->willReturn('Hi there!');
        $message2->expects($this->once())->method('getCreateTime')->willReturn(new \DateTimeImmutable('2023-01-01 10:01:00'));
        $message2->expects($this->exactly(2))->method('getToolCalls')->willReturn(['tool' => 'test']);

        $messages = [$message1, $message2];

        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($conversation);

        $this->messageRepository->expects($this->once())
            ->method('findByConversation')
            ->with($conversation)
            ->willReturn($messages);

        $request = new Request();
        $response = $this->controller->__invoke($request, 1);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
        
        $responseData = json_decode($response->getContent(), true);
        $this->assertCount(2, $responseData['messages']);
        $this->assertEquals(2, $responseData['total']);
        
        // 验证第一条消息
        $this->assertEquals('msg-1', $responseData['messages'][0]['id']);
        // 枚举在序列化时包含详细信息，我们检查value字段
        $this->assertIsArray($responseData['messages'][0]['role']);
        $this->assertEquals('user', $responseData['messages'][0]['role']['value']);
        $this->assertEquals('Hello', $responseData['messages'][0]['content']);
        $this->assertEquals('2023-01-01 10:00:00', $responseData['messages'][0]['createdAt']);
        $this->assertArrayNotHasKey('toolCalls', $responseData['messages'][0]);
        
        // 验证第二条消息
        $this->assertEquals('msg-2', $responseData['messages'][1]['id']);
        $this->assertIsArray($responseData['messages'][1]['role']);
        $this->assertEquals('assistant', $responseData['messages'][1]['role']['value']);
        $this->assertEquals('Hi there!', $responseData['messages'][1]['content']);
        $this->assertEquals('2023-01-01 10:01:00', $responseData['messages'][1]['createdAt']);
        $this->assertArrayHasKey('toolCalls', $responseData['messages'][1]);
        $this->assertEquals(['tool' => 'test'], $responseData['messages'][1]['toolCalls']);
    }
}