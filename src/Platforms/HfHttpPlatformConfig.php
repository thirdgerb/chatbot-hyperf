<?php


namespace Commune\Chatbot\Hyperf\Platforms;


use Commune\Chatbot\Hyperf\Platforms\Http\HfHttpConfig;
use Commune\Chatbot\Hyperf\Platforms\Http\HfHttpPlatform;
use Commune\Platform\IPlatformConfig;

class HfHttpPlatformConfig extends IPlatformConfig
{
    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',
            'desc' => '',
            'bootShell' => null,
            'bootGhost' => false,
            'concrete' => HfHttpPlatform::class,
            'providers' => [
            ],
            'options' => [
                HfHttpConfig::class => [
                    'server' => [
                        'name' => '',
                        'host' => '127.0.0.1',
                        'port' => 9501,
                    ],
                    'processes' => [],
                    'routes' => [
                    ],
                ],
            ],
        ];
    }


}