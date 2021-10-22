<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Lib\Trait;

use Hyperf\Utils\Str;

trait CamelCase
{
    public function getAttribute($key)
    {
        return parent::getAttribute($key) ?? parent::getAttribute(Str::snake($key));
    }

    public function setAttribute($key, $value)
    {
        return parent::setAttribute(Str::snake($key), $value);
    }
}