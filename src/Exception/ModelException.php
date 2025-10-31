<?php

namespace OpenAIBundle\Exception;

class ModelException extends OpenAiException
{
    /**
     * @param array<string> $supportedModels
     */
    public static function unsupportedModel(string $model, array $supportedModels): self
    {
        return new self(sprintf(
            '不支持的模型 "%s"。支持的模型有：%s',
            $model,
            implode(', ', $supportedModels)
        ));
    }
}
