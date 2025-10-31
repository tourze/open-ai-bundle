<?php

namespace OpenAIBundle\Exception;

class DataValidationException extends \InvalidArgumentException
{
    public static function missingRequiredFields(string $message): self
    {
        return new self(sprintf('数据验证失败: %s', $message));
    }
}
