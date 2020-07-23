<?php


namespace Commune\Chatlog\Platform;


use Commune\Chatlog\ChatlogSocketIOServiceProvider;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SocketIOController;
use Commune\Chatbot\Hyperf\Platforms\HfSocketIOPlatformConfig;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOOption;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;
use Commune\Framework\Providers\LoggerByMonologProvider;

/**
 * @author thirdgerb <thirdgerb@gmail.com>
 */
class ChatlogSocketIOPlatformConfig extends HfSocketIOPlatformConfig
{

    public static function stub(): array
    {
        return [
            'id' => 'chatlog_socketio',
            'name' => 'chatlog socket.io 平台',
            'desc' => '基于 Socket.io 启动的 Websocket 平台, 与前端对接',
            'concrete' => HfSocketIOPlatform::class,
            'bootShell' => 'chatlog',
            'bootGhost' => false,
            'providers' => [
                // 日志配置.
                LoggerByMonologProvider::class => [
                    'name' => 'chatlog',
                    'forceRegister' => true,
                ],
                // Chatlog 服务提供.
                ChatlogSocketIOServiceProvider::class => [
                    'jwtSecret' => env('CHATLOG_JWT_SECRET', 'helple~~ss'),
                ],
            ],
            'options' => [
                HfSocketIOOption::class => [
                    'servers' => [
                        [
                            'name' => 'chatlog_1',
                            'host' => env('CHATLOG_SOCKET_IO_HOST', '127.0.0.1'),
                            'port' => env('CHATLOG_SOCKET_IO_PORT', 9510),
                        ],
                    ],
                    'processes' => [
                        // AsyncMessageProcess::class,
                    ],
                    'settings' => [],
                    'controller' => SocketIOController::class,
                    'namespaces' => [],
                ],

            ],
        ];
    }

}