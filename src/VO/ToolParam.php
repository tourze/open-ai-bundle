<?php

namespace OpenAIBundle\VO;

use OpenAIBundle\Enum\ToolType;

/**
 * @see https://www.volcengine.com/docs/82379/1298454#toolparam
 */
class ToolParam
{
    public function __construct(
        private readonly ToolType $type,
        private readonly FunctionDefinition $function,
    ) {
    }

    public function getType(): ToolType
    {
        return $this->type;
    }

    public function getFunction(): FunctionDefinition
    {
        return $this->function;
    }
}
