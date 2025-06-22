<?php

namespace OpenAIBundle\Controller;

use OpenAIBundle\Repository\ConversationRepository;
use OpenAIBundle\Repository\MessageRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ConversationPageController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly MessageRepository $messageRepository,
    ) {
    }

    #[Route('/open-ai/conversation/{id}', name: 'open_ai_conversation', requirements: ['id' => '\d+'])]
    public function __invoke(Request $request, int $id): Response
    {
        $conversation = $this->conversationRepository->find($id);
        if (null === $conversation) {
            throw $this->createNotFoundException();
        }

        $messages = $this->messageRepository->findByConversation($conversation);

        return $this->render('@OpenAI/chat/conversation.html.twig', [
            'conversation' => $conversation,
            'messages' => $messages,
        ]);
    }
}