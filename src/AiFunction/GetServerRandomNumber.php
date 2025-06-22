<?php

namespace OpenAIBundle\AiFunction;

use OpenAIBundle\Enum\FunctionParamType;
use OpenAIBundle\VO\FunctionParam;

class GetServerRandomNumber implements AiFunctionInterface
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
        $result = rand($parameters['min'] ?? 0, $parameters['max'] ?? PHP_INT_MAX);

        // dump(__METHOD__, $parameters, $result);
        return (string) $result;
    }
}
