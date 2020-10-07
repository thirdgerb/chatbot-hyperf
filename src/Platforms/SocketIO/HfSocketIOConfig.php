<?php

namespace Commune\Chatbot\Hyperf\Platforms\SocketIO;


use Hyperf\SocketIOServer;
use Commune\Blueprint\CommuneEnv;
use Commune\Support\Utils\StringUtils;
use Commune\Support\Option\AbsOption;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;

/**
 * Hyperf 中 websocket 服务端使用的配置.
 *
 * @property-read HfSocketIOServerOption    $server     Hyperf Server 的配置.
 *
 * @property-read string[] $processes       Server 的子进程.
 *
 *
 * @property-read string $path              访问地址
 * @property-read string[] $namespaces      命名空间与控制器的关系
 * @property-read string $controller        默认的控制器
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
            'sidProvider' => SocketIOServer\SidProvider\LocalSidProvider::class,
            'roomProvider' => SocketIOServer\Room\RedisAdapter::class,
        ];
    }

    public static function relations(): array
    {
        return [
            'server' => HfSocketIOServerOption::class,
        ];
    }


    /**
     * 将当前配置转化为标准的 Hyperf 配置.
     * @return HfPlatformOption
     */
    public function toHyperfPlatformOption() : HfPlatformOption
    {
        $servers = [];

        $name = $this->server->name;
        $serverData = $this->server->toArray();
        $serverData['settings']['pid_file'] = $serverData['settings']['pid_file']
            ?? StringUtils::gluePath(
                CommuneEnv::getRuntimePath(),
                "pid/$name.pid"
            );

        $servers[] = $serverData;

        $data = [];
        $data['mode'] = SWOOLE_PROCESS;
        $data['type'] = \Hyperf\Server\Server::class;
        $data['servers'] = $servers;
        $data['processes'] = $this->processes;

        return new HfPlatformOption($data);
    }




}