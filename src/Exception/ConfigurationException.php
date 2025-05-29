<?php

namespace OpenAIBundle\Exception;

class ConfigurationException extends OpenAiException
{
    public static function configurationNotFound(string $id): self
    {
        return new self(sprintf('未找到 ID 为 %s 的 DeepSeek 配置', $id));
    }

    public static function characterNotFound(string $id): self
    {
        return new self(sprintf('角色配置未找到：%s', $id));
    }

    public static function characterNotActive(string $id): self
    {
        return new self(sprintf('角色未启用：%s', $id));
    }
}
