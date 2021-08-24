<?php

declare(strict_types=1);

return [
    'service_center_host' => env('TREASURE_BAG_SERVICE_CENTER_HOST', 'http://localhost'),
    'service' => ['name' => '', 'description' => '', 'path' => [], 'publish' => [['topic' => '', 'description' => '']]],
    'service_hostname' => env('TREASURE_BAG_SERVICE_HOSTNAME', 'localhost'),
    'service_port' => (int)env('TREASURE_BAG_SERVICE_PORT', 9501),
];
