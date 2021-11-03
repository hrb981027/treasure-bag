<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Exception\Handler;

use Hrb981027\TreasureBag\Exception\StandardException;
use Hrb981027\TreasureBag\Lib\ResponseContent\StandardResponseContent;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class StandardExceptionHandler extends ExceptionHandler
{
    public function handle(Throwable $throwable, ResponseInterface $response): MessageInterface
    {
        $this->stopPropagation();

        $standardResponseContent = new StandardResponseContent();

        $standardResponseContent
            ->setCode($throwable->getCode())
            ->setMessage($throwable->getMessage());

        return $response->json($standardResponseContent->toArray());
    }

    public function isValid(Throwable $throwable): bool
    {
        if ($throwable instanceof StandardException) {
            return true;
        }

        return false;
    }
}
