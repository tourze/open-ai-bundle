<?php

declare(strict_types=1);

namespace OpenAIBundle\Exception;

class OpenAiGenericException extends OpenAiException
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
