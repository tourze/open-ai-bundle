<?php

namespace OpenAIBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum ToolType: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case function = 'function';

    public function getLabel(): string
    {
        return match ($this) {
            self::function => '函数',
        };
    }
}
