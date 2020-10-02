<?php


namespace Commune\Chatbot\Hyperf\Platforms\SocketIO;


use Commune\Chatbot\Hyperf\Servers\HfServerOption;
use Hyperf\Server\ServerInterface;
use Hyperf\Server\SwooleEvent;
use Commune\Chatbot\Hyperf\Hacks\Websocket\HfWebsocketServer as Server;
use Commune\Chatbot\Hyperf\Hacks\Websocket\HfWebsocketExceptionHandler;

/**
 * Hyperf SocketIO 端的 Server 配置.
 */
class HfSocketIOServerOption extends HfServerOption
{

    public static function stub(): array
    {
        return [
            'name' => '',
            'host' => '127.0.0.1',
            'port' => 9503,
            'mode' => SWOOLE_PROCESS,
            'type' => ServerInterface::SERVER_WEBSOCKET,
            'sock_type' => SWOOLE_SOCK_TCP,
            'callbacks' => [
                SwooleEvent::ON_HAND_SHAKE => [Server::class, 'onHandShake'],
                SwooleEvent::ON_MESSAGE => [Server::class, 'onMessage'],
                SwooleEvent::ON_CLOSE => [Server::class, 'onClose'],
            ],

            'settings' => [
            ],
            'middlewares' => null,
            'exceptionHandlers' => [
                HfWebsocketExceptionHandler::class,
            ],
        ];
    }

}