<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatbot\Hyperf\Coms\SocketIO\EventHandler;
use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\Room;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class LeaveHandler extends EventHandler
{
    protected $middlewares = [
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
        AuthorizePipe::class,
    ];

    function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $room = new Room($request->proto);

        $socket->leave($room->session);
        $user = $request->user;

        $session = $room->session;
        $socket->join($session);

        $text = TextMessage::instance($user->name . ' 离开了对话');
        $response = $request->makeResponse(
            MessageBatch::fromSystem($session, $text)
        );
        $socket->to($session)->emit($response->event, $response->toEmit());

        return [];
    }


}