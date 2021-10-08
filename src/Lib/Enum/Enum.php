<?php

namespace Hrb981027\TreasureBag\Lib\Enum;

use Hyperf\Constants\AbstractConstants;

class Enum extends AbstractConstants
{
    public static function toArray()
    {
        $r = new \ReflectionClass(static::class);
        return $r->getConstants();
    }

    public static function inArray($value)
    {
        return in_array($value, array_values(self::toArray()));
    }
}
