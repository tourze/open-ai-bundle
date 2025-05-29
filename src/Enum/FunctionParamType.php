<?php

namespace OpenAIBundle\Enum;

/**
 * 支持有限的类型
 */
enum FunctionParamType: string
{
    case string = 'string';
    case integer = 'integer';
    case boolean = 'boolean';
}
