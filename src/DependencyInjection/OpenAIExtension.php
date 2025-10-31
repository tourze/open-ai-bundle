<?php

namespace OpenAIBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class OpenAIExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
