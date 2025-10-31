<?php

namespace OpenAIBundle\Controller;

use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ChatIndexController extends AbstractController
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
    ) {
    }

    #[Route(path: '/open-ai/chat', name: 'open_ai_chat')]
    public function __invoke(): Response
    {
        $characters = $this->characterRepository->findBy(['valid' => true]);
        $apiKeys = $this->apiKeyRepository->findBy(['valid' => true]);

        return $this->render('@OpenAI/chat/index.html.twig', [
            'characters' => $characters,
            'api_keys' => $apiKeys,
        ]);
    }
}
