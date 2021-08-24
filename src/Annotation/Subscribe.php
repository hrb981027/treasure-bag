<?php

declare(strict_types=1);

namespace TreasureBag\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class Subscribe extends AbstractAnnotation
{
    public array $topic;
}