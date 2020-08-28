<?php

namespace Commune\Chatbot\Hyperf\Platforms\SocketIO;


use Hyperf\SocketIOServer;
use Commune\Support\Option\AbsOption;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;
use Commune\Support\Swoole\ServerSettingOption;

/**
 * Hyperf 中 websocket 服务端使用的配置.
 *
 * @property-read HfSocketIOServerOption[] $servers
 * @property-read ServerSettingOption $settings
 *
 * @property-read string[] $processes       Server 的子进程.
 *
 *
 * @property-read string $path
 * @property-read string[] $namespaces
 * @property-read string $controller
 *
 * @property-read string    $sidProvider    hyperf socket.io session id 的提供者.
 * @property-read string    $roomProvider   hyperf socket.io 房间适配器.
 */
class HfSocketIOConfig extends AbsOption
{

    public static function stub(): array
    {
        return [

            'servers' => [],

            'path' => '/socket.io/',
            'controller' => SocketIOExampleController::class,
            'namespaces' => [
//                '/nsp' => SocketIOExampleController::class,
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
            'servers[]' => HfSocketIOServerOption::class,
        ];
    }


    public function toHyperfPlatformOption() : HfPlatformOption
    {
        $servers = [];

        $settings = $this->settings->toArray();


        foreach ($this->servers as $serverOption) {
            $name = $serverOption->name;

            $serverData = $serverOption->toArray();
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