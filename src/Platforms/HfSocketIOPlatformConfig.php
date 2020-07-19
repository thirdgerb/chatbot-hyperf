<?php


namespace Commune\Chatbot\Hyperf\Platforms;


use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOOption;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;
use Commune\Platform\IPlatformConfig;

/**
 * Hyperf Socket IO 平台的基本配置.
 */
class HfSocketIOPlatformConfig extends IPlatformConfig
{
    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',
            'desc' => '',
            'concrete' => HfSocketIOPlatform::class,
            'bootShell' => null,
            'bootGhost' => false,
            'providers' => [

            ],
            'options' => [
                HfSocketIOOption::class => [
                    'servers' => [
                        'name' => '',
                        'host' => '127.0.0.1',
                        'port' => 9503,
                    ],
                    'processes' => [],
                    'settings' => [],
                    'routes' => [
                    ],
                ],
            ],
        ];
    }

}