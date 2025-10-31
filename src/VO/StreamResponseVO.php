<?php

namespace OpenAIBundle\VO;

abstract class StreamResponseVO
{
    /**
     * @param array<int, ChoiceVO> $choices
     */
    public function __construct(
        public readonly string $id,
        public readonly int $created,
        public readonly string $model,
        public readonly string $systemFingerprint,
        public readonly string $object,
        public readonly array $choices,
        public readonly ?UsageVO $usage = null,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    abstract public static function fromArray(array $data): self;

    public function getPromptTokens(): ?int
    {
        return $this->usage?->getPromptTokens();
    }

    public function getCompletionTokens(): ?int
    {
        return $this->usage?->getCompletionTokens();
    }

    public function getTotalTokens(): ?int
    {
        return $this->usage?->getTotalTokens();
    }

    public function getMsgId(): string
    {
        return $this->id;
    }
}
