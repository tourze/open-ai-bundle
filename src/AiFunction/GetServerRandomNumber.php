<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;
use Tourze\MCPContracts\ToolInterface;

class GetServerRandomNumber implements ToolInterface
{
    public function getName(): string
    {
        return 'GetServerRandomNumber';
    }

    public function getDescription(): string
    {
        return '生成一个服务端随机数';
    }

    public function getParameters(): \Traversable
    {
        yield new FunctionParam('min', FunctionParamType::integer, '最小值', false);
        yield new FunctionParam('max', FunctionParamType::integer, '最大值', true);
    }

    public function execute(array $parameters = []): string
    {
        $min = $parameters['min'] ?? 0;
        $max = $parameters['max'] ?? PHP_INT_MAX;

        if (!is_int($min)) {
            $min = 0;
        }
        if (!is_int($max)) {
            $max = PHP_INT_MAX;
        }

        $result = rand($min, $max);

        // dump(__METHOD__, $parameters, $result);
        return (string) $result;
    }
}
