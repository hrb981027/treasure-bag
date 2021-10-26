<?php

declare(strict_types=1);

namespace Hrb981027\TreasureBag;

class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => [],
            'commands' => [],
            'annotations' => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
            'publish' => [
                [
                    'id' => 'config',
                    'description' => 'The config of treasure bag.',
                    'source' => __DIR__ . '/../publish/treasureBag.php',
                    'destination' => BASE_PATH . '/config/autoload/treasureBag.php',
                ],
            ],
        ];
    }
}
