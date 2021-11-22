<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 */
class ParamProperty extends AbstractAnnotation
{
    public bool $required = false;
    public bool $filled = false;
    public bool $allowIn = true;
    public string $in = '';
    public bool $allowOut = true;
    public string $out = '';
}