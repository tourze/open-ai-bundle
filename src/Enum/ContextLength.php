<?php

namespace OpenAIBundle\Enum;

use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

/**
 * 模型支持的上下文长度
 */
enum ContextLength: int implements Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;

    case K_4 = 4096;     // 4K
    case K_8 = 8192;     // 8K
    case K_16 = 16384;   // 16K
    case K_32 = 32768;   // 32K
    case K_64 = 65536;
    case K_128 = 131072; // 128K

    public function getLabel(): string
    {
        return match ($this) {
            self::K_4 => '4K',
            self::K_8 => '8K',
            self::K_16 => '16K',
            self::K_32 => '32K',
            self::K_64 => '64K',
            self::K_128 => '128K',
        };
    }
}
