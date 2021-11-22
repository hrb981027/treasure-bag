<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Annotation;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Param extends AbstractAnnotation
{
    public string $inHandle = 'camelize';
    public string $outHandle = 'uncamelize';
}