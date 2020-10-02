<?php


namespace Commune\Chatbot\Hyperf\Platforms\Http;


use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;
use Commune\Support\Option\AbsOption;
use Commune\Support\Swoole\ServerSettingOption;

/**
 * hyperf http 服务端相关配置.
 * @property HfHttpServerOption[] $servers          服务端的配置.
 * @property string[] $processes                    服务端启动时运行的子进程
 * @property ServerSettingOption $settings          Swoole server 的基本配置
 * @property string[] $routes                       启动时加载的路由文件. 可以放平台相关的配置文件.
 */
class HfHttpConfig extends AbsOption
{

    public static function stub(): array
    {
        return [
            'servers' => [],
            'processes' => [],
            'settings' => [],
            'routes' => [],
        ];
    }

    public static function relations(): array
    {
        return [
            'settings' => ServerSettingOption::class,
            'servers[]' => HfHttpServerOption::class,
        ];
    }


    /**
     * 将当前配置转化为标准的 Hyperf 配置.
     * @return HfPlatformOption
     */
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