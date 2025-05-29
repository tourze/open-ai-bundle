<?php

namespace OpenAIBundle\VO;

use OpenAIBundle\Enum\FunctionParamType;

/**
 * AI函数的参数定义
 */
class FunctionParam
{
    public function __construct(
        private readonly string $name,
        private readonly FunctionParamType $type,
        private readonly string $description,
        private readonly bool $required,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getType(): FunctionParamType
    {
        return $this->type;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function isRequired(): bool
    {
        return $this->required;
    }
}
