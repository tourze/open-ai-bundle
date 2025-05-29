<?php

namespace OpenAIBundle\VO;

class UsageVO
{
    public function __construct(
        private readonly int $promptTokens,
        private readonly int $completionTokens,
        private readonly int $totalTokens,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            $data['prompt_tokens'],
            $data['completion_tokens'],
            $data['total_tokens']
        );
    }

    public function getPromptTokens(): int
    {
        return $this->promptTokens;
    }

    public function getCompletionTokens(): int
    {
        return $this->completionTokens;
    }

    public function getTotalTokens(): int
    {
        return $this->totalTokens;
    }
}
