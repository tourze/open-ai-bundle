<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use Tourze\DoctrineEntityMarkdownBundle\Service\EntityService;
use Tourze\MCPContracts\ToolInterface;

class GetTableFields implements ToolInterface
{
    public function __construct(
        /** @var EntityService<object> $entityService */
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
        $tableName = $parameters['table_name'] ?? '';
        if (!is_string($tableName) || '' === $tableName) {
            $result = json_encode(['error' => '表名不能为空']);

            return false !== $result ? $result : '';
        }

        return $this->entityService->getTableFields($tableName);
    }
}
