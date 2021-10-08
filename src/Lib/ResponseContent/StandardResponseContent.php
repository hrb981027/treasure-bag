<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Lib\ResponseContent;

use Hrb981027\TreasureBag\Lib\Enum\ResponseCode;

class StandardResponseContent
{
    private int $code;

    private string $message;

    private $data;

    public function __construct()
    {
        $this->code = ResponseCode::SUCCESS;
        $this->message = '';
        $this->data = null;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function setCode(int $code): self
    {
        $this->code = $code;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getData()
    {
        return $this->data;
    }

    public function setData($data): self
    {
        $this->data = $data;

        return $this;
    }

    public function toArray(): array
    {
        return [
            'code' => $this->code,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    public function toJson(): string
    {
        return json_encode($this->toArray(), JSON_UNESCAPED_UNICODE);
    }
}
