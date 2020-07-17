<?php


namespace Commune\Chatbot\Hyperf\Servers;

use Commune\Support\Swoole\ServerSettingOption;
use Hyperf\Server\Server;
use Commune\Support\Option\AbsOption;

/**
 * Hyperf 里通用的 Server 配置.
 *
 * @property-read string                    $name       名称
 * @property-read int                       $type       Hyperf 服务端类型.
 *                                                      @see \Hyperf\Server\Server
 *
 * @property-read int                       $sock_type  Swoole 定义的 socket type.
 * @property-read string                    $host       127.0.0.1 或 0.0.0.0
 * @property-read int                       $mode
 * @property-read int                       $port       监听端口.
 * @property-read string[]                  $callbacks  监听事件.
 * @property-read ServerSettingOption       $settings   Swoole Server 的配置.
 *
 */
class HfServerOption extends AbsOption
{
    const IDENTITY = 'name';

    public static function stub(): array
    {
        return [
            'name' => '',
            'host' => '127.0.0.1',
            'port' => 9503,
            'type' => Server::SERVER_BASE,
            'mode' => SWOOLE_BASE,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
            ],
            'settings' => [
            ],

        ];
    }

    public static function relations(): array
    {
        return [
            'settings' => ServerSettingOption::class,
        ];
    }


}
