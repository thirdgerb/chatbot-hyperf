<?php

namespace Commune\Chatbot\Hyperf\Platforms\SocketIO;


use Hyperf\SocketIOServer;
use Hyperf\WebSocketServer;
use Hyperf\Server\SwooleEvent;
use Hyperf\Server\ServerInterface;
use Commune\Support\Option\AbsOption;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;
use Commune\Chatbot\Hyperf\Servers\HfServerOption;
use Commune\Support\Swoole\ServerSettingOption;

/**
 * Hyperf 中 websocket 服务端使用的配置.
 *
 * @property-read HfServerOption[] $servers
 * @property-read ServerSettingOption $settings
 *
 * @property-read string[] $processes       Server 的子进程.
 *
 * @property-read string[]  $routes         [namespace => controller]
 *
 *
 * @property-read string    $sidProvider    hyperf socket.io session id 的提供者.
 * @property-read string    $roomProvider   hyperf socket.io 房间适配器.
 */
class HfSocketIOOption extends AbsOption
{

    public static function stub(): array
    {
        return [

            'servers' => [],

            'routes' => [
                '/' => SocketIOExampleController::class,
            ],

            'processes' => [],

            'settings' => [],

            'sidProvider' => SocketIOServer\SidProvider\LocalSidProvider::class,
            'roomProvider' => SocketIOServer\Room\RedisAdapter::class,

        ];
    }

    public static function relations(): array
    {
        return [
            'settings' => ServerSettingOption::class,
            'servers' => HfServerOption::class,
        ];
    }


    public function toHyperfPlatformOption() : HfPlatformOption
    {
        $servers = [];

        $settings = $this->settings->toArray();

        $server = [
            'type' => ServerInterface::SERVER_WEBSOCKET,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                SwooleEvent::ON_HAND_SHAKE => [WebSocketServer\Server::class, 'onHandShake'],
                SwooleEvent::ON_MESSAGE => [WebSocketServer\Server::class, 'onMessage'],
                SwooleEvent::ON_CLOSE => [WebSocketServer\Server::class, 'onClose'],
            ],
        ];

        foreach ($this->servers as $serverOption) {
            $name = $serverOption->name;

            $serverData = $server + $serverOption->toArray();
            $serverData['settings'] = $settings;
            $serverData['settings']['pid_file'] =
                $serverData['settings']['pid_file']
                ?? BASE_PATH . "/runtime/pid/$name.pid";

            $servers[] = $serverData;
        }

        $data = [];
        $data['servers'] = $servers;
        $data['processes'] = $this->processes;

        return new HfPlatformOption($data);
    }




}