<?php


namespace Commune\Chatbot\Hyperf\Platforms\Http;


use Commune\Chatbot\Hyperf\Servers\HfServerOption;
use Hyperf\Server\ServerInterface;
use Hyperf\Server\SwooleEvent;
use Hyperf\HttpServer\Server;
use Commune\Chatbot\Hyperf\Hacks\Http;

/**
 * Http 端的默认 Server option.
 */
class HfHttpServerOption extends HfServerOption
{

    public static function stub(): array
    {
        return [
            'name' => '',
            'host' => '127.0.0.1',
            'port' => 9501,
            'mode' => SWOOLE_PROCESS,
            'type' => ServerInterface::SERVER_HTTP,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                SwooleEvent::ON_REQUEST => [Server::class, 'onRequest'],
            ],
            'settings' => [
            ],
            'middlewares' => null,
            'exceptionHandlers' => [
                Http\HfHttpExceptionHandler::class,
            ],
        ];
    }

}