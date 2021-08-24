<?php

declare(strict_types=1);

namespace TreasureBag\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Secure extends AbstractAnnotation
{
    public string $path = '';
}