<?php


namespace Commune\Chatbot\Hyperf\Platforms\Http;


use Commune\Blueprint\CommuneEnv;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;
use Commune\Support\Option\AbsOption;
use Commune\Support\Utils\StringUtils;

/**
 * hyperf http 服务端相关配置.
 * @property HfHttpServerOption $server             服务端的配置.
 * @property string[] $processes                    服务端启动时运行的子进程
 * @property string[] $routes                       启动时加载的路由文件. 可以放平台相关的配置文件.
 */
class HfHttpConfig extends AbsOption
{

    public static function stub(): array
    {
        return [
            'server' => [],
            'processes' => [],
            'routes' => [],
        ];
    }

    public static function relations(): array
    {
        return [
            'server' => HfHttpServerOption::class,
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
        $data['servers'] = $servers;
        $data['processes'] = $this->processes;
        $data['mode'] = SWOOLE_PROCESS;
        $data['type'] = \Hyperf\Server\Server::class;

        return new HfPlatformOption($data);
    }



}