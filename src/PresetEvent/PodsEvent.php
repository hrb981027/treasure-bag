<?php

declare(strict_types=1);

namespace TreasureBag\PresetEvent;

class PodsEvent
{
    public const MEMBER_CREATED = 'member.created';

    public const MESSAGE = [
        self::MEMBER_CREATED => '成员创建'
    ];
}