<?php

namespace OpenAIBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\Entity\Message;
use OpenAIBundle\Enum\RoleEnum;
use OpenAIBundle\Repository\ConversationRepository;
use OpenAIBundle\VO\ChoiceVO;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\UsageVO;
use Symfony\Component\Uid\Uuid;

class ConversationService
{
    public function __construct(
        private readonly ConversationRepository $conversationRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * 初始化会话
     */
    public function initConversation(Character $character, ApiKey $apiKey): Conversation
    {
        $conversation = new Conversation();
        $conversation->setTitle("与{$character->getName()}对话");
        $conversation->setModel($apiKey->getModel());
        $conversation->setActor($character);
        $conversation->setSystemPrompt($character->getSystemPrompt());
        $conversation->setValid(true);

        if (!empty($character->getSystemPrompt())) {
            // 设置角色的系统提示词
            $message = new Message();
            $message->setApiKey($apiKey);
            $message->setModel($apiKey->getModel());
            $message->setMsgId(Uuid::v4()->toRfc4122());
            $message->setRole(RoleEnum::system);
            $message->setContent($character->getSystemPrompt());
            $conversation->addMessage($message);
        }

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }

    /**
     * 将用户的消息添加到历史
     */
    public function createUserMessage(Conversation $conversation, ApiKey $apiKey, string $content): Message
    {
        $message = new Message();
        $message->setApiKey($apiKey);
        $message->setModel($apiKey->getModel());
        $message->setMsgId(Uuid::v4()->toRfc4122());
        $message->setRole(RoleEnum::user);
        $message->setContent($content);
        $conversation->addMessage($message);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $message;
    }

    public function createToolMessage(Conversation $conversation, ApiKey $apiKey, string $content, string $toolCallId): Message
    {
        $message = new Message();
        $message->setApiKey($apiKey);
        $message->setModel($apiKey->getModel());
        $message->setMsgId(Uuid::v4()->toRfc4122());
        $message->setRole(RoleEnum::tool);
        $message->setContent($content);
        $message->setToolCallId($toolCallId);
        $conversation->addMessage($message);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * 如果消息没创建，那么我们要更新消息。
     * 定位到消息了，我们要更新文本
     */
    public function appendAssistantContent(Conversation $conversation, ApiKey $apiKey, string $msgId, string $content): Message
    {
        $foundMessage = $this->appendAssistantMessage($conversation, $apiKey, $msgId);
        $foundMessage->appendContent($content);

        return $foundMessage;
    }

    public function appendAssistantReasoningContent(Conversation $conversation, ApiKey $apiKey, StreamChunkVO $chunk, ChoiceVO $choice): void
    {
        $foundMessage = $this->appendAssistantMessage($conversation, $apiKey, $chunk->getMsgId());
        $foundMessage->appendReasoningContent(strval($choice->getReasoningContent()));
    }

    public function appendAssistantUsage(Conversation $conversation, ApiKey $apiKey, StreamChunkVO $chunk, UsageVO $usage): void
    {
        $foundMessage = $this->appendAssistantMessage($conversation, $apiKey, $chunk->getMsgId());
        $foundMessage->setPromptTokens($usage->getPromptTokens());
        $foundMessage->setCompletionTokens($usage->getCompletionTokens());
        $foundMessage->setTotalTokens($usage->getTotalTokens());
    }

    /**
     * 如果消息没创建，那么我们要更新消息。
     * 定位到消息了，我们要 1更新文本 2更新tool_calls
     */
    public function appendAssistantToolCalls(Conversation $conversation, ApiKey $apiKey, StreamChunkVO $chunk, ChoiceVO $choice): void
    {
        $foundMessage = $this->appendAssistantMessage($conversation, $apiKey, $chunk->getMsgId());

        // 附加 tool_calls
        if ($choice->getToolCalls()) {
            foreach ($choice->getToolCalls() as $toolCall) {
                $foundMessage->addToolCall($toolCall);
            }
        }
    }

    public function getMessageArray(Conversation $conversation): array
    {
        $result = [];
        foreach ($conversation->getMessages() as $message) {
            $result[] = $message->toArray();
        }

        return $result;
    }

    /**
     * 创建系统消息
     */
    public function createSystemMessage(Conversation $conversation, ApiKey $apiKey, string $content): Message
    {
        $message = new Message();
        $message->setApiKey($apiKey);
        $message->setModel($apiKey->getModel());
        $message->setMsgId(Uuid::v4()->toRfc4122());
        $message->setRole(RoleEnum::system);
        $message->setContent($content);
        $conversation->addMessage($message);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $message;
    }

    /**
     * 创建角色消息
     */
    public function createMessage(Conversation $conversation, ApiKey $apiKey, RoleEnum $role, string $content): Message
    {
        $message = new Message();
        $message->setApiKey($apiKey);
        $message->setModel($apiKey->getModel());
        $message->setMsgId(Uuid::v4()->toRfc4122());
        $message->setRole($role);
        $message->setContent($content);
        $conversation->addMessage($message);

        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $message;
    }

    private function appendAssistantMessage(Conversation $conversation, ApiKey $apiKey, string $msgId): Message
    {
        $message = null;
        foreach ($conversation->getMessages() as $item) {
            if ($item->getMsgId() !== $msgId) {
                continue;
            }
            $message = $item;
        }

        if (!$message) {
            $message = new Message();
            $message->setApiKey($apiKey);
            $message->setModel($apiKey->getModel());
            $message->setMsgId($msgId);
            $message->setRole(RoleEnum::assistant);
            $message->setContent('');
            $message->setReasoningContent('');

            // 要保存起来
            $conversation->addMessage($message);
        }

        return $message;
    }
}
