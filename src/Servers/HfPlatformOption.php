<?php


namespace Commune\Chatbot\Hyperf\Servers;


use Commune\Support\Option\AbsOption;
use Commune\Support\Swoole\ServerSettingOption;
use Hyperf\Server\Server;

/**
 * 使用 Hyperf 作为服务端平台的配置.
 * 为 Hyperf 的 Server 提供相关配置数据.
 *
 * @property-read string $type
 * @property-read int $mode
 * @property-read HfServerOption[] $servers
 * @property-read string[] $processes
 * @property-read array $settings
 * @property-read array[] $callbacks
 */
class HfPlatformOption extends AbsOption
{
    public static function stub(): array
    {
        return [
            'mode' => SWOOLE_PROCESS,
            'servers' => [
            ],
            'type' => Server::class,
            'processes' => [],
            'settings' => [
            ],
            'callbacks' => [
            ],
        ];
    }

    public static function relations(): array
    {
        return [
            'servers[]' => HfServerOption::class,
        ];
    }

    public function toServerConfigArray() : array
    {
        return $this->toArray();
    }

}