<?php

namespace OpenAIBundle\Service;

use OpenAIBundle\Entity\ApiKey;
use OpenAIBundle\Entity\Character;
use OpenAIBundle\Entity\Conversation;
use OpenAIBundle\VO\StreamChunkVO;
use OpenAIBundle\VO\StreamRequestOptions;
use OpenAIBundle\VO\StreamResponseVO;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * 处理 AI 对话响应的服务类
 */
class ChatResponseHandler
{
    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly ConversationService $conversationService,
        private readonly FunctionService $functionService,
    ) {
    }

    /**
     * @param array<mixed> $tools
     */
    public function fetchResponse(
        OutputInterface $output,
        Character $character,
        ApiKey $apiKey,
        Conversation $conversation,
        array $tools,
        bool $debug,
        bool $noStream = false,
        bool $isQuiet = false,
    ): void {
        $options = $this->buildRequestOptions($character, $apiKey, $tools, $debug);

        if ($noStream) {
            $this->handleNonStreamResponse($output, $apiKey, $conversation, $tools, $debug, $isQuiet, $options);
        } else {
            $this->handleStreamResponse($output, $character, $apiKey, $conversation, $tools, $debug, $isQuiet, $options);
        }
    }

    /**
     * @param array<mixed> $tools
     */
    private function buildRequestOptions(Character $character, ApiKey $apiKey, array $tools, bool $debug): StreamRequestOptions
    {
        return new StreamRequestOptions(
            debug: $debug,
            model: $apiKey->getModel(),
            temperature: $character->getTemperature(),
            topP: $character->getTopP(),
            maxTokens: $character->getMaxTokens(),
            presencePenalty: $character->getPresencePenalty(),
            frequencyPenalty: $character->getFrequencyPenalty(),
            tools: [] !== $tools ? $tools : null,
        );
    }

    /**
     * @param array<mixed> $tools
     */
    private function handleNonStreamResponse(
        OutputInterface $output,
        ApiKey $apiKey,
        Conversation $conversation,
        array $tools,
        bool $debug,
        bool $isQuiet,
        StreamRequestOptions $options,
    ): void {
        $response = $this->openAiService->chat(
            $apiKey,
            $this->conversationService->getMessageArray($conversation),
            $options,
        );

        $this->processNonStreamResponse($output, $conversation, $apiKey, $response, $tools, $debug, $isQuiet);
    }

    /**
     * @param array<mixed> $tools
     */
    private function handleStreamResponse(
        OutputInterface $output,
        Character $character,
        ApiKey $apiKey,
        Conversation $conversation,
        array $tools,
        bool $debug,
        bool $isQuiet,
        StreamRequestOptions $options,
    ): void {
        foreach ($this->openAiService->streamReasoner(
            $apiKey,
            $this->conversationService->getMessageArray($conversation),
            $options,
        ) as $chunk) {
            $shouldRefetch = $this->processStreamChunk(
                $output,
                $conversation,
                $apiKey,
                $chunk,
                $tools
            );

            if ($shouldRefetch) {
                $this->fetchResponse($output, $character, $apiKey, $conversation, $tools, $debug, false, $isQuiet);
            }
        }
    }

    /**
     * @param array<mixed> $tools
     * @param mixed $chunk
     */
    private function processStreamChunk(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        $chunk,
        array $tools,
    ): bool {
        $shouldRefetch = false;

        foreach ($chunk->getChoices() as $choice) {
            $this->processChoiceContent($output, $conversation, $apiKey, $chunk, $choice);
            $this->processChoiceReasoning($output, $conversation, $apiKey, $chunk, $choice);

            if ($this->processChoiceToolCalls($conversation, $apiKey, $choice, $chunk)) {
                $shouldRefetch = true;
            }
        }

        $this->processUsage($conversation, $apiKey, $chunk);

        return $shouldRefetch;
    }

    /**
     * @param mixed $chunk
     * @param mixed $choice
     */
    private function processChoiceContent(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        $chunk,
        $choice,
    ): void {
        if (null !== $choice->getContent()) {
            $this->conversationService->appendAssistantContent(
                $conversation,
                $apiKey,
                $chunk->getMsgId(),
                strval($choice->getContent()),
            );
            if (strlen($choice->getContent()) > 0) {
                $output->write($choice->getContent());
            }
        }
    }

    /**
     * @param mixed $chunk
     * @param mixed $choice
     */
    private function processChoiceReasoning(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        $chunk,
        $choice,
    ): void {
        if (null !== $choice->getReasoningContent()) {
            $this->conversationService->appendAssistantReasoningContent($conversation, $apiKey, $chunk, $choice);
            if (strlen($choice->getReasoningContent()) > 0) {
                $output->write("<comment>{$choice->getReasoningContent()}</comment>");
            }
        }
    }

    /**
     * @param mixed $choice
     * @param mixed $chunk
     */
    private function processChoiceToolCalls(
        Conversation $conversation,
        ApiKey $apiKey,
        $choice,
        $chunk,
    ): bool {
        if (null !== $choice->getToolCalls() && [] !== $choice->getToolCalls()) {
            $this->conversationService->appendAssistantToolCalls($conversation, $apiKey, $chunk, $choice);

            foreach ($choice->getDecodeToolCalls() as $toolCall) {
                $this->conversationService->createToolMessage(
                    $conversation,
                    $apiKey,
                    $this->functionService->invoke($toolCall),
                    $toolCall->getId(),
                );
            }

            return true;
        }

        return false;
    }

    /**
     * @param mixed $chunk
     */
    private function processUsage(
        Conversation $conversation,
        ApiKey $apiKey,
        $chunk,
    ): void {
        $usage = $chunk->getUsage();
        if (null !== $usage) {
            $this->conversationService->appendAssistantUsage($conversation, $apiKey, $chunk, $usage);
        }
    }

    /**
     * @param array<mixed> $tools
     */
    private function processNonStreamResponse(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
        array $tools,
        bool $debug,
        bool $isQuiet = false,
    ): void {
        foreach ($response->choices as $choice) {
            $this->processNonStreamChoice($output, $conversation, $apiKey, $response, $choice, $tools, $debug, $isQuiet);
        }

        $this->processNonStreamUsage($conversation, $apiKey, $response);
    }

    /**
     * @param array<mixed> $tools
     * @param mixed $choice
     */
    private function processNonStreamChoice(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
        $choice,
        array $tools,
        bool $debug,
        bool $isQuiet,
    ): void {
        $this->processNonStreamContent($output, $conversation, $apiKey, $response, $choice);
        $this->processNonStreamReasoning($output, $conversation, $apiKey, $response, $choice);
        $this->processNonStreamToolCalls($output, $conversation, $apiKey, $response, $choice, $tools, $debug, $isQuiet);
    }

    /**
     * @param mixed $choice
     */
    private function processNonStreamContent(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
        $choice,
    ): void {
        if (null === $choice->getContent()) {
            return;
        }

        $this->conversationService->appendAssistantContent(
            $conversation,
            $apiKey,
            $response->getMsgId(),
            strval($choice->getContent()),
        );

        if (strlen($choice->getContent()) > 0) {
            $output->write($choice->getContent());
        }
    }

    /**
     * @param mixed $choice
     */
    private function processNonStreamReasoning(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
        $choice,
    ): void {
        if (null === $choice->getReasoningContent()) {
            return;
        }

        $tempChunk = $this->createTempChunk($response, [$choice]);
        $this->conversationService->appendAssistantReasoningContent($conversation, $apiKey, $tempChunk, $choice);

        if (strlen($choice->getReasoningContent()) > 0) {
            $output->write("<comment>{$choice->getReasoningContent()}</comment>");
        }
    }

    /**
     * @param array<mixed> $tools
     * @param mixed $choice
     */
    private function processNonStreamToolCalls(
        OutputInterface $output,
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
        $choice,
        array $tools,
        bool $debug,
        bool $isQuiet,
    ): void {
        if (null === $choice->getToolCalls() || [] === $choice->getToolCalls()) {
            return;
        }

        $tempChunk = $this->createTempChunk($response, [$choice]);
        $this->conversationService->appendAssistantToolCalls($conversation, $apiKey, $tempChunk, $choice);

        foreach ($choice->getDecodeToolCalls() as $toolCall) {
            $this->conversationService->createToolMessage(
                $conversation,
                $apiKey,
                $this->functionService->invoke($toolCall),
                $toolCall->getId(),
            );
        }

        // 重新请求处理函数调用结果
        $actor = $conversation->getActor();
        if (null !== $actor) {
            $this->fetchResponse($output, $actor, $apiKey, $conversation, $tools, $debug, true, $isQuiet);
        }
    }

    private function processNonStreamUsage(
        Conversation $conversation,
        ApiKey $apiKey,
        StreamResponseVO $response,
    ): void {
        if (null === $response->usage) {
            return;
        }

        $tempChunk = $this->createTempChunk($response, $response->choices);
        $this->conversationService->appendAssistantUsage($conversation, $apiKey, $tempChunk, $response->usage);
    }

    /**
     * @param array<mixed> $choices
     */
    private function createTempChunk(StreamResponseVO $response, array $choices): StreamChunkVO
    {
        return new StreamChunkVO(
            $response->id,
            $response->created,
            $response->model,
            $response->systemFingerprint,
            $response->object,
            $choices,
            $response->usage
        );
    }
}
