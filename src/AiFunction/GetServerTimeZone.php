<?php

namespace OpenAIBundle\AiFunction;

use Tourze\MCPContracts\ToolInterface;

class GetServerTimeZone implements ToolInterface
{
    public const NAME = 'GetServerTimeZone';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): string
    {
        return '获取服务器当前时区信息';
    }

    public function getParameters(): \Traversable
    {
        return new \EmptyIterator();
    }

    public function execute(array $parameters = []): string
    {
        return date_default_timezone_get();
    }
}
