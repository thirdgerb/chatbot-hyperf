<?php


namespace Commune\Chatbot\Hyperf\Servers;

use Commune\Support\Swoole\ServerSettingOption;
use Hyperf\Server\Server;
use Commune\Support\Option\AbsOption;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Psr\Http\Server\MiddlewareInterface;

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
 * @property-read array[]                   $callbacks  监听事件.
 *
 * @property-read ServerSettingOption       $settings   Swoole Server 的配置.
 * @property-read string[]|null             $middelware Hyperf ServerName 的中间件.
 * @property-read string[]|null             $exceptionHandlers 异常处理器.
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
            'mode' => SWOOLE_PROCESS,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
            ],
            'settings' => [
            ],
            'middlewares' => [
            ],
            'exceptionHandlers' => [

            ],


        ];
    }

    public static function relations(): array
    {
        return [
            'settings' => ServerSettingOption::class,
        ];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        $middlewares = $data['middlewares'] ?? [];
        foreach ($middlewares as $middleware) {
            if (!is_a($middleware, MiddlewareInterface::class, TRUE)) {
                return "middleware should be subclass of " . MiddlewareInterface::class . ", $middleware given";
            }
        }

        $exceptionHandlers = $data['exceptionHandlers'] ?? [];
        foreach ($exceptionHandlers as $exceptionHandler) {
            if (!is_a($exceptionHandler, ExceptionHandler::class, true)) {
                return 'exceptionHandler should be subclass of ' .ExceptionHandler::class . ", $exceptionHandler given";
            }
        }

        return parent::validate($data);
    }

}
