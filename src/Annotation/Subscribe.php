<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Subscribe extends AbstractAnnotation
{
    public array $topic;
}