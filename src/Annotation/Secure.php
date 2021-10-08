<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS", "METHOD"})
 */
class Secure extends AbstractAnnotation
{
    public string $path = '';
}