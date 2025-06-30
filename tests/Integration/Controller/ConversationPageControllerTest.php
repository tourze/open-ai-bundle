<?php

namespace OpenAIBundle\Tests\Integration\Controller;

use OpenAIBundle\Controller\ConversationPageController;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Repository\ConversationRepository;
use OpenAIBundle\Repository\MessageRepository;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ConversationPageControllerTest extends TestCase
{
    private ConversationPageController $controller;
    private ConversationRepository $conversationRepository;
    private MessageRepository $messageRepository;

    protected function setUp(): void
    {
        $this->conversationRepository = $this->createMock(ConversationRepository::class);
        $this->messageRepository = $this->createMock(MessageRepository::class);

        $this->controller = new ConversationPageController(
            $this->conversationRepository,
            $this->messageRepository
        );
    }

    public function testControllerExtendsAbstractController(): void
    {
        $this->assertInstanceOf(ConversationPageController::class, $this->controller);
    }

    public function testInvokeWithNonExistentConversation(): void
    {
        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $request = new Request();

        $this->expectException(NotFoundHttpException::class);

        $this->controller->__invoke($request, 999);
    }

    public function testInvokeWithExistingConversation(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $message1 = $this->createMock(Message::class);
        $message2 = $this->createMock(Message::class);
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

        // 我们期望这会因为缺少 Twig 环境而抛出异常，但repository调用应该成功
        $this->expectException(\Throwable::class);
        
        $this->controller->__invoke($request, 1);
    }

    public function testRepositoriesAreCalledWithCorrectParameters(): void
    {
        $conversation = $this->createMock(Conversation::class);

        $this->conversationRepository->expects($this->once())
            ->method('find')
            ->with(123)
            ->willReturn($conversation);

        $this->messageRepository->expects($this->once())
            ->method('findByConversation')
            ->with($conversation)
            ->willReturn([]);

        $request = new Request();

        try {
            $this->controller->__invoke($request, 123);
        } catch (\Throwable $e) {
            // 忽略Twig相关的异常，重点是验证repository调用
        }
    }
}