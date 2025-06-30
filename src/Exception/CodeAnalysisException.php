<?php

namespace OpenAIBundle\Exception;

class CodeAnalysisException extends \RuntimeException
{
    public static function classNotFound(string $filepath): self
    {
        return new self(sprintf('无法从文件 %s 中提取类名', $filepath));
    }
}