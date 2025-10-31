<?php

namespace OpenAIBundle\VO;

use OpenAIBundle\Exception\DataValidationException;

class UsageVO
{
    public function __construct(
        private readonly int $promptTokens,
        private readonly int $completionTokens,
        private readonly int $totalTokens,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!isset($data['prompt_tokens'], $data['completion_tokens'], $data['total_tokens'])) {
            throw DataValidationException::missingRequiredFields('Missing required fields in usage data');
        }

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
