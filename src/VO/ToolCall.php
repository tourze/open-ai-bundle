<?php

namespace OpenAIBundle\VO;

class ToolCall
{
    public function __construct(
        private readonly string $id,
        private readonly string $index,
        private readonly string $type,
        private readonly string $functionName,
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

    public function getFunctionArguments(): array
    {
        return $this->functionArguments;
    }

    public function getIndex(): string
    {
        return $this->index;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
