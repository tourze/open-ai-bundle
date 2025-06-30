<?php

namespace OpenAIBundle\AiFunction;

use Doctrine\DBAL\Connection;
use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\Exception\SqlQueryException;
use OpenAIBundle\VO\FunctionParam;
use Tourze\MCPContracts\ToolInterface;

class FetchSqlResult implements ToolInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function getName(): string
    {
        return 'FetchSqlResult';
    }

    public function getDescription(): string
    {
        return '执行指定的SQL查询并返回结果集。仅支持 SELECT 语句，且必须包含 LIMIT 子句。';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam(
            name: 'sql',
            type: FunctionParamType::string,
            description: '要执行的SQL查询语句，必须是 SELECT 语句且包含 LIMIT 子句',
            required: true,
        );
    }

    public function execute(array $parameters = []): string
    {
        $sql = trim($parameters['sql'] ?? '');
        if (empty($sql)) {
            throw SqlQueryException::emptySql();
        }

        // 安全检查：只允许 SELECT 语句
        if (!str_starts_with(strtoupper($sql), 'SELECT ')) {
            throw SqlQueryException::unsupportedStatement();
        }

        //        // 检查是否包含 LIMIT
        //        if (!preg_match('/\bLIMIT\s+\d+\b/i', $sql)) {
        //            throw new \InvalidArgumentException('SQL 语句必须包含 LIMIT 子句');
        //        }

        try {
            // 执行查询
            $stmt = $this->connection->executeQuery($sql);
            $results = $stmt->fetchAllAssociative();

            if (empty($results)) {
                return json_encode([
                    'total' => 0,
                    'data' => [],
                    'sql' => $sql,
                ], JSON_UNESCAPED_UNICODE);
            }

            // 获取字段信息
            $columns = array_keys($results[0]);

            return json_encode([
                'total' => count($results),
                'columns' => $columns,
                'data' => $results,
                'sql' => $sql,
            ], JSON_UNESCAPED_UNICODE);
        } catch (\Throwable $e) {
            return json_encode([
                'error' => $e->getMessage(),
                'sql' => $sql,
            ], JSON_UNESCAPED_UNICODE);
        }
    }
}
