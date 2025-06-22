<?php

namespace OpenAIBundle\Controller;

use OpenAIBundle\Exception\ConfigurationException;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\ConversationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class ChatCreateController extends AbstractController
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route('/open-ai/chat/create', name: 'open_ai_chat_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        $content = $request->toArray();
        $characterId = $content['characterId'] ?? null;
        $apiKeyId = $content['apiKeyId'] ?? null;

        $character = $this->characterRepository->find($characterId);
        if (null === $character) {
            throw ConfigurationException::characterNotFound($characterId);
        }

        $apiKey = $this->apiKeyRepository->find($apiKeyId);
        if (null === $apiKey) {
            throw ConfigurationException::configurationNotFound($apiKeyId);
        }

        $conversation = $this->conversationService->initConversation($character, $apiKey);

        return $this->json([
            'conversationId' => $conversation->getId(),
            'description' => $conversation->getDescription(),
        ]);
    }
}