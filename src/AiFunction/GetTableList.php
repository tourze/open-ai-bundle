<?php

namespace OpenAIBundle\AiFunction;

use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\MCPContracts\ToolInterface;

class GetTableList implements ToolInterface
{
    public const NAME = 'GetTableList';

    public function __construct(
        private readonly EntityService $entityService,
    ) {
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDescription(): string
    {
        return '返回数据库中所有表名和说明';
    }

    public function getParameters(): \Traversable
    {
        return new \ArrayIterator([]);
    }

    public function execute(array $parameters = []): string
    {
        return $this->entityService->getAllTableNames();
    }
}
