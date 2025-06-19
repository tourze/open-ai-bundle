<?php

namespace OpenAIBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum RoleEnum: string implements Itemable, Labelable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case system = 'system';
    case user = 'user';
    case assistant = 'assistant';
    case tool = 'tool';

    public function getLabel(): string
    {
        return match ($this) {
            self::system => '系统',
            self::user => '用户',
            self::assistant => '助手',
            self::tool => '工具',
        };
    }
}
