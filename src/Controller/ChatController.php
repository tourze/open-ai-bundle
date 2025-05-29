<?php

namespace OpenAIBundle\Controller;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\ApiKeyRepository;
use OpenAIBundle\Repository\CharacterRepository;
use OpenAIBundle\Repository\ConversationRepository;
use OpenAIBundle\Repository\MessageRepository;
use OpenAIBundle\Service\ConversationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;
use Tourze\DoctrineRandomBundle\Service\RandomService;

class ChatController extends AbstractController
{
    public function __construct(
        private readonly CharacterRepository $characterRepository,
        private readonly ConversationRepository $conversationRepository,
        private readonly MessageRepository $messageRepository,
        private readonly ApiKeyRepository $apiKeyRepository,
        private readonly RandomService $randomService,
        private readonly EntityManagerInterface $entityManager,
        private readonly ConversationService $conversationService,
    ) {
    }

    #[Route('/open-ai/chat', name: 'open_ai_chat')]
    public function chat(): Response
    {
        $characters = $this->characterRepository->findBy(['valid' => true]);
        $apiKeys = $this->apiKeyRepository->findBy(['valid' => true]);

        return $this->render('@OpenAi/chat.html.twig', [
            'characters' => $characters,
            'api_keys' => $apiKeys,
        ]);
    }

    #[Route('/open-ai/chat/conversations', name: 'open_ai_chat_conversations', methods: ['GET'])]
    public function conversations(): JsonResponse
    {
        $conversations = $this->conversationRepository->findBy(
            ['valid' => true],
            ['createTime' => 'DESC']
        );

        $data = array_map(function (Conversation $conversation) {
            return [
                'id' => $conversation->getId(),
                'title' => $conversation->getTitle(),
                'actor' => [
                    'id' => $conversation->getActor()->getId(),
                    'name' => $conversation->getActor()->getName(),
                    'avatar' => $conversation->getActor()->getAvatar(),
                ],
            ];
        }, $conversations);

        return new JsonResponse($data);
    }

    #[Route('/open-ai/chat/conversation/{id}', name: 'open_ai_chat_conversation_detail', methods: ['GET'])]
    public function conversationDetail(string $id): JsonResponse
    {
        $conversation = $this->conversationRepository->findOneBy(['id' => $id, 'valid' => true]);
        if (!$conversation) {
            throw $this->createNotFoundException('会话不存在');
        }

        $messages = array_values(array_filter($conversation->getMessages()->toArray(), function (Message $message) {
            return in_array($message->getRole(), [RoleEnum::user, RoleEnum::assistant]);
        }));

        return new JsonResponse([
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'actor' => [
                'id' => $conversation->getActor()->getId(),
                'name' => $conversation->getActor()->getName(),
                'avatar' => $conversation->getActor()->getAvatar(),
            ],
            'messages' => array_map(function (Message $message) {
                return [
                    'role' => $message->getRole()->value,
                    'content' => $message->getContent(),
                    'model' => $message->getModel(),
                    'usage' => [
                        'prompt_tokens' => $message->getPromptTokens(),
                        'completion_tokens' => $message->getCompletionTokens(),
                        'total_tokens' => $message->getTotalTokens(),
                    ],
                ];
            }, $messages),
        ]);
    }

    #[Route('/open-ai/chat/create', name: 'open_ai_chat_create', methods: ['POST'])]
    public function create(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $characterId = $data['characterId'] ?? '';
        $apiKeyId = $data['apiKeyId'] ?? '';
        $title = $data['title'] ?? '';

        $character = $this->characterRepository->findOneBy(['id' => $characterId, 'valid' => true]);
        if (!$character) {
            throw $this->createNotFoundException('角色不存在');
        }

        $apiKey = $this->apiKeyRepository->findOneBy(['id' => $apiKeyId, 'valid' => true]);
        if (!$apiKey) {
            throw $this->createNotFoundException('API key not found');
        }

        $conversation = $this->conversationService->initConversation($character, $apiKey);

        return new JsonResponse([
            'id' => $conversation->getId(),
            'title' => $conversation->getTitle(),
            'actor' => [
                'id' => $conversation->getActor()->getId(),
                'name' => $conversation->getActor()->getName(),
                'avatar' => $conversation->getActor()->getAvatar(),
            ],
            'messages' => array_map(function (Message $message) {
                return [
                    'role' => $message->getRole()->value,
                    'content' => $message->getContent(),
                    'model' => $message->getModel(),
                    'usage' => [
                        'prompt_tokens' => $message->getPromptTokens(),
                        'completion_tokens' => $message->getCompletionTokens(),
                        'total_tokens' => $message->getTotalTokens(),
                    ],
                ];
            }, $conversation->getMessages()->toArray()),
        ], 201);
    }

    #[Route('/open-ai/chat/reply', name: 'openai_chat_reply', methods: ['POST'])]
    public function reply(Request $request): StreamedResponse
    {
        $data = json_decode($request->getContent(), true);
        $characterId = $data['characterId'] ?? '';
        $conversationId = $data['conversationId'] ?? '';
        $message = $data['message'] ?? '';
        $apiKeyId = $data['apiKeyId'] ?? '';
        $model = $data['model'] ?? '';

        $character = $this->characterRepository->findOneBy(['id' => $characterId, 'valid' => true]);
        if (!$character) {
            return new StreamedResponse(function () {
                echo 'data: ' . json_encode(['content' => '角色不存在', 'done' => true]) . "\n\n";
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        $apiKey = $this->apiKeyRepository->findOneBy(['id' => $apiKeyId, 'valid' => true]);
        if (!$apiKey) {
            return new StreamedResponse(function () {
                echo 'data: ' . json_encode(['content' => 'API Key不存在', 'done' => true]) . "\n\n";
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        $conversation = $this->conversationRepository->findOneBy(['id' => $conversationId, 'valid' => true]);
        if (!$conversation) {
            return new StreamedResponse(function () {
                echo 'data: ' . json_encode(['content' => '会话不存在', 'done' => true]) . "\n\n";
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        }

        // 保存用户消息
        $this->conversationService->createUserMessage($conversation, $apiKey, $message);

        // 随机获取一条助手消息作为回复
        $qb = $this->messageRepository->createQueryBuilder('m')
            ->where('m.role = :role')
            ->setParameter('role', RoleEnum::assistant);
        $randomMessage = iterator_to_array($this->randomService->getRandomResult($qb));

        if (!$randomMessage) {
            return new StreamedResponse(function () {
                echo 'data: ' . json_encode(['content' => '没有找到可用的消息', 'done' => true]) . "\n\n";
                flush();
            }, 200, [
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
                'Connection' => 'keep-alive',
                'X-Accel-Buffering' => 'no',
            ]);
        }
        $randomMessage = reset($randomMessage);
        /** @var Message $randomMessage */

        // 创建新的助手消息
        $assistantMessage = $this->conversationService->appendAssistantContent(
            $conversation,
            $apiKey,
            uniqid('msg_'),
            '',
        );

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return new StreamedResponse(function () use ($randomMessage, $assistantMessage) {
            $content = $randomMessage->getContent();

            // 模拟流式输出
            $chars = mb_str_split($content);
            foreach ($chars as $char) {
                echo 'data: ' . json_encode(['content' => $char, 'done' => false]) . "\n\n";
                flush();
                usleep(50000); // 50ms delay

                // 累积内容
                $assistantMessage->appendContent($char);
            }

            // 保存完整消息
            $this->entityManager->flush();

            // 发送完成标记
            echo 'data: ' . json_encode(['content' => '', 'done' => true]) . "\n\n";
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'Connection' => 'keep-alive',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
