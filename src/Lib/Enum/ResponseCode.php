<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag\Lib\Enum;

use Hyperf\Constants\Annotation\Constants;

/**
 * @Constants()
 */
class ResponseCode extends Enum
{
    /**
     * @Message("成功")
     */
    const SUCCESS = 1;

    /**
     * @Message("失败")
     */
    const ERROR = -1;

    /**
     * @Message("授权失败，可能原因：尚未登录、权限不足")
     */
    const AUTH_ERROR = -2;

    /**
     * @Message("验证字段失败")
     */
    const VALIDATION_ERROR = -3;
}