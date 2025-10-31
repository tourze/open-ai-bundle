<?php

namespace OpenAIBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum FunctionParamType: string implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case string = 'string';
    case integer = 'integer';
    case boolean = 'boolean';

    public function getLabel(): string
    {
        return match ($this) {
            self::string => '字符串',
            self::integer => '整数',
            self::boolean => '布尔值',
        };
    }
}
