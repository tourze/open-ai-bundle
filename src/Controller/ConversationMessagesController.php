<?php

namespace OpenAIBundle\Controller;

use OpenAIBundle\Repository\ConversationRepository;
use OpenAIBundle\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ConversationMessagesController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly MessageRepository $messageRepository,
    ) {
    }

    #[Route(path: '/open-ai/conversation/{id}/messages', name: 'open_ai_conversation_messages', requirements: ['id' => '\d+'])]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);
        if (null === $conversation) {
            return $this->json(['error' => 'Conversation not found'], 404);
        }

        $messages = $this->messageRepository->findByConversation($conversation);

        $messagesData = [];
        foreach ($messages as $message) {
            $data = [
                'id' => $message->getId(),
                'role' => $message->getRole(),
                'content' => $message->getContent(),
                'createdAt' => $message->getCreateTime()?->format('Y-m-d H:i:s'),
            ];

            if ($message->getToolCalls()) {
                $data['toolCalls'] = $message->getToolCalls();
            }

            $messagesData[] = $data;
        }

        return $this->json([
            'messages' => $messagesData,
            'total' => count($messages),
        ]);
    }
}