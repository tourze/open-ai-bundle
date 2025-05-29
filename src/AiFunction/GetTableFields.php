<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;

class GetTableFields implements AiFunctionInterface
{
    public function __construct(
        private readonly EntityService $entityService,
    ) {
    }

    public function getName(): string
    {
        return 'GetTableFields';
    }

    public function getDescription(): string
    {
        return '返回指定表的所有字段定义，包含字段名、类型和说明，调用前应使用 ' . GetTableList::NAME . ' 获取表名';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('table_name', FunctionParamType::string, '表名', true);
    }

    public function execute(array $parameters = []): string
    {
        return $this->entityService->getTableFields($parameters['table_name']);
    }
}
