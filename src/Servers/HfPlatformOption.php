<?php


namespace Commune\Chatbot\Hyperf\Servers;


use Commune\Support\Option\AbsOption;
use Commune\Support\Swoole\ServerSettingOption;
use Hyperf\Server\Server;

/**
 * 使用 Hyperf 作为服务端平台的配置.
 *
 * @property-read string $type
 * @property-read int $mode
 * @property-read HfServerOption[] $servers
 * @property-read string[] $processes
 * @property-read ServerSettingOption $settings
 * @property-read array[] $callbacks
 */
class HfPlatformOption extends AbsOption
{
    public static function stub(): array
    {
        return [
            'servers' => [
            ],
            'type' => Server::class,
            'mode' => SWOOLE_BASE,
            'processes' => [],
            'settings' => [],
            'callbacks' => [],
        ];
    }

    public static function relations(): array
    {
        return [
            'servers[]' => HfServerOption::class,
            'settings' => ServerSettingOption::class,
        ];
    }

    public function toServerConfigArray() : array
    {
        return $this->toArray();
    }

}