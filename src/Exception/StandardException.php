<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Exception;

use Hrb981027\TreasureBag\Lib\Enum\ResponseCode;

class StandardException extends \Exception
{
    public function __construct($message = "")
    {
        parent::__construct($message, ResponseCode::ERROR);
    }
}