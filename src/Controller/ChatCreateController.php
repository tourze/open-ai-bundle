<?php

namespace OpenAIBundle\Controller;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Exception\ConfigurationException;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Service\ConversationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatCreateController extends AbstractController
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route(path: '/open-ai/chat/create', name: 'open_ai_chat_create', methods: ['POST'])]
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $content = $request->toArray();
            $characterId = $content['characterId'] ?? null;
            $apiKeyId = $content['apiKeyId'] ?? null;

            if (null === $characterId) {
                return $this->json(['error' => 'characterId is required'], Response::HTTP_BAD_REQUEST);
            }

            if (null === $apiKeyId) {
                return $this->json(['error' => 'apiKeyId is required'], Response::HTTP_BAD_REQUEST);
            }

            $character = $this->characterRepository->find($characterId);
            if (null === $character) {
                return $this->json(['error' => sprintf('Character not found: %s', $characterId)], Response::HTTP_BAD_REQUEST);
            }
            assert($character instanceof Character);

            $apiKey = $this->apiKeyRepository->find($apiKeyId);
            if (null === $apiKey) {
                return $this->json(['error' => sprintf('API key not found: %s', $apiKeyId)], Response::HTTP_BAD_REQUEST);
            }
            assert($apiKey instanceof ApiKey);

            $conversation = $this->conversationService->initConversation($character, $apiKey);

            return $this->json([
                'conversationId' => $conversation->getId(),
                'description' => $conversation->getDescription(),
            ]);
        } catch (ConfigurationException $e) {
            return $this->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        }
    }
}
