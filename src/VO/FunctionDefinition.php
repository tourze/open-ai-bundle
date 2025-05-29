<?php

namespace OpenAIBundle\VO;

/**
 * @see https://www.volcengine.com/docs/82379/1298454#functiondefinition
 */
class FunctionDefinition
{
    public function __construct(
        private readonly string $name,
        private readonly string $description,
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
}
