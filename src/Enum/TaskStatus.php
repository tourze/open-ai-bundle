<?php

namespace OpenAIBundle\Enum;

use Tourze\EnumExtra\BadgeInterface;
use Tourze\EnumExtra\Itemable;
use Tourze\EnumExtra\ItemTrait;
use Tourze\EnumExtra\Labelable;
use Tourze\EnumExtra\Selectable;
use Tourze\EnumExtra\SelectTrait;

enum TaskStatus: string implements BadgeInterface, Labelable, Itemable, Selectable
{
    use ItemTrait;
    use SelectTrait;
    case PENDING = 'pending';
    case RUNNING = 'running';
    case COMPLETED = 'completed';
    case FAILED = 'failed';

    public function getLabel(): string
    {
        return match ($this) {
            self::PENDING => '待处理',
            self::RUNNING => '正在运行',
            self::COMPLETED => '已完成',
            self::FAILED => '执行失败',
        };
    }

    public function getBadge(): string
    {
        return match ($this) {
            self::PENDING => self::INFO,
            self::RUNNING => self::PRIMARY,
            self::COMPLETED => self::SUCCESS,
            self::FAILED => self::DANGER,
        };
    }
}
