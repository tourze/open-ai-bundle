<?php

namespace OpenAIBundle\VO;

class ToolCall
{
    public function __construct(
        private readonly string $id,
        private readonly int|string $index,
        private readonly string $type,
        private readonly string $functionName,
        /** @var array<string, mixed> $functionArguments */
        private readonly array $functionArguments,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getFunctionName(): string
    {
        return $this->functionName;
    }

    /**
     * @return array<string, mixed>
     */
    public function getFunctionArguments(): array
    {
        return $this->functionArguments;
    }

    public function getIndex(): int|string
    {
        return $this->index;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
