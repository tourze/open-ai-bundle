<?php

namespace OpenAIBundle\Exception;

class SqlQueryException extends \InvalidArgumentException
{
    public static function emptySql(): self
    {
        return new self('SQL语句不能为空');
    }

    public static function unsupportedStatement(): self
    {
        return new self('仅支持 SELECT 语句');
    }

    public static function limitClauseRequired(): self
    {
        return new self('SQL 语句必须包含 LIMIT 子句');
    }
}
