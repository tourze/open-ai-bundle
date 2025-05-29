<?php

namespace OpenAIBundle\VO;

class StreamResponseVO
{
    /**
     * @param array<ChoiceVO> $choices
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

    public static function fromArray(array $data): self
    {
        $choices = array_map(
            fn (array $choice) => ChoiceVO::fromArray($choice),
            $data['choices']
        );

        return new self(
            $data['id'],
            $data['created'],
            $data['model'],
            $data['system_fingerprint'] ?? '',
            $data['object'],
            $choices,
            isset($data['usage']) ? UsageVO::fromArray($data['usage']) : null
        );
    }

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
