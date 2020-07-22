<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Blueprint\EventHandler;
use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Chatlog\SocketIO\Middleware\TokenAuthorizePipe;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\Room;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class JoinHandler extends EventHandler
{
    protected $middlewares = [
        TokenAuthorizePipe::class,
    ];

    function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $user = $request->user;
        if (empty($user)) {
            return [static::class => 'user is empty'];
        }

        $room = new Room($request->proto);
        //todo 可能有权限问题.

        $session = $room->session;
        $socket->join($session);

        $text = TextMessage::instance($user->name . ' 加入了对话');
        $response = $request->makeResponse(
            MessageBatch::fromSystem($session, $text)
        );
        $socket->to($session)->emit($response->event, $response->toEmit());

        return [];
    }


}