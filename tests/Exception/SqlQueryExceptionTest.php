<?php

namespace OpenAIBundle\Tests\Exception;

use OpenAIBundle\Exception\SqlQueryException;
use PHPUnit\Framework\TestCase;

class SqlQueryExceptionTest extends TestCase
{
    public function testEmptySqlException(): void
    {
        $exception = SqlQueryException::emptySql();

        $this->assertInstanceOf(SqlQueryException::class, $exception);
        $this->assertEquals('SQL语句不能为空', $exception->getMessage());
    }

    public function testUnsupportedStatementException(): void
    {
        $exception = SqlQueryException::unsupportedStatement();

        $this->assertInstanceOf(SqlQueryException::class, $exception);
        $this->assertEquals('仅支持 SELECT 语句', $exception->getMessage());
    }

    public function testLimitClauseRequiredException(): void
    {
        $exception = SqlQueryException::limitClauseRequired();

        $this->assertInstanceOf(SqlQueryException::class, $exception);
        $this->assertEquals('SQL 语句必须包含 LIMIT 子句', $exception->getMessage());
    }

    public function testExceptionExtendsInvalidArgumentException(): void
    {
        $exception = SqlQueryException::emptySql();
        
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
    }
}