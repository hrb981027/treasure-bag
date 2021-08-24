<?php

declare(strict_types=1);

namespace TreasureBag\Service\PresetService;

use TreasureBag\PresetEvent\PodsEvent;

class PodsService
{
    public const MEMBER_SERVICE = [
        'name' => 'member-service',
        'description' => '成员服务',
        'path' => [
            '/member-service'
        ],
        'publish' => [
            [
                'topic' => PodsEvent::MEMBER_CREATED,
                'description' => PodsEvent::MESSAGE[PodsEvent::MEMBER_CREATED]
            ]
        ]
    ];
}
