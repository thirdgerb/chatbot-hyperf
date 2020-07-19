<?php


namespace Commune\Chatbot\Hyperf\Servers;


use Commune\Support\Option\AbsOption;
use Commune\Support\Swoole\ServerSettingOption;
use Hyperf\Server\Server;
use Hyperf\Server\SwooleEvent;
use Hyperf\Framework\Bootstrap;

/**
 * 使用 Hyperf 作为服务端平台的配置.
 * 为 Hyperf 的 Server 提供相关配置数据.
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
            'mode' => SWOOLE_PROCESS,
            'servers' => [
            ],
            'type' => Server::class,
            'processes' => [],
            'settings' => [
                'enable_coroutine' => true,
                'worker_num' => swoole_cpu_num(),
                // 'pid_file' => BASE_PATH . '/runtime/hyperf.pid',
                'open_tcp_nodelay' => true,
                'max_coroutine' => 100000,
                'open_http2_protocol' => true,
                'max_request' => 100000,
                'socket_buffer_size' => 2 * 1024 * 1024,
                'buffer_output_size' => 2 * 1024 * 1024,
            ],
            'callbacks' => [
                SwooleEvent::ON_WORKER_START => [Bootstrap\WorkerStartCallback::class, 'onWorkerStart'],
                SwooleEvent::ON_PIPE_MESSAGE => [Bootstrap\PipeMessageCallback::class, 'onPipeMessage'],
                SwooleEvent::ON_WORKER_EXIT => [Bootstrap\WorkerExitCallback::class, 'onWorkerExit'],
            ],
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