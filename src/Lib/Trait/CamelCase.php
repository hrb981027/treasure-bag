<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Lib\Trait;

use Hyperf\Utils\Str;

trait CamelCase
{
    public function getAttribute($key)
    {
        return parent::getAttribute(Str::snake($key)) ?? parent::getAttribute($key);
    }

    public function setAttribute($key, $value)
    {
        return parent::setAttribute(Str::snake($key), $value);
    }
}