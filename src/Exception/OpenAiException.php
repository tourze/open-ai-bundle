<?php

namespace OpenAIBundle\Exception;

class OpenAiException extends \RuntimeException
{
    public static function apiRequestFailed(string $message): self
    {
        return new self(sprintf('OpenAi API请求失败: %s', $message));
    }

    public static function invalidConfiguration(string $message): self
    {
        return new self(sprintf('OpenAi配置无效: %s', $message));
    }
}
