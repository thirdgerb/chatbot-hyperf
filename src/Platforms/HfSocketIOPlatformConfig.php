<?php


namespace Commune\Chatbot\Hyperf\Platforms;


use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOConfig;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;
use Commune\Platform\IPlatformConfig;

/**
 * 基于 Hyperf Socket IO 实现的平台的基本配置.
 */
class HfSocketIOPlatformConfig extends IPlatformConfig
{
    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',
            'desc' => '',
            'bootShell' => null,
            'bootGhost' => false,
            'concrete' => HfSocketIOPlatform::class,
            'providers' => [
            ],
            'options' => [
                HfSocketIOConfig::class => [
                    'server' => [
                        'name' => '',
                        'host' => '127.0.0.1',
                        'port' => 9503,
                    ],
                    'processes' => [],
                ],
            ],
        ];
    }

}