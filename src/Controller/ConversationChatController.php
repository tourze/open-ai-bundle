<?php

namespace OpenAIBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Exception\ConfigurationException;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\ConversationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ConversationChatController extends AbstractController
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '/open-ai/conversation/{id}/chat', name: 'open_ai_conversation_chat', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function __invoke(Request $request, int $id): JsonResponse
    {
        $conversation = $this->conversationRepository->find($id);
        if (null === $conversation) {
            throw $this->createNotFoundException();
        }

        $content = $request->toArray();
        $message = $content['message'] ?? '';
        $apiKeyId = $content['apiKeyId'] ?? null;

        $apiKey = null === $apiKeyId ? null : $this->apiKeyRepository->find($apiKeyId);
        if (null === $apiKey) {
            // 从 Character 获取默认的 API key
            $character = $conversation->getActor();
            if (null !== $character) {
                $apiKey = $character->getPreferredApiKey();
            }
        }

        if (!$apiKey instanceof ApiKey) {
            throw ConfigurationException::configurationNotFound('API Key');
        }

        $userMessage = new Message();
        $userMessage->setMsgId('msg_' . uniqid());
        $userMessage->setConversation($conversation);
        $userMessage->setRole(RoleEnum::user);
        $userMessage->setContent($message);
        $this->entityManager->persist($userMessage);
        $this->entityManager->flush();

        // TODO: 实现流式响应
        return $this->json([
            'message' => 'Message received',
            'messageId' => $userMessage->getId(),
        ]);
    }
}