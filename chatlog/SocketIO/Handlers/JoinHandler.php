<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\RoomProtocalPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\DTO\RoomInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Protocal\JoinedRoom;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class JoinHandler extends ChatlogEventHandler
{
    protected $middlewares = [
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
        AuthorizePipe::class,
        RoomProtocalPipe::class,
    ];

    function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $user = $request->getTemp(UserInfo::class);
        /**
         * @var RoomInfo $room
         */
        $room = $request->getTemp(RoomInfo::class);

        // 加入房间.
        $session = $room->session;
        $socket->join($session);

        // 广播系统消息.
        $response = $this->makeSystemResponse(
            TextMessage::instance($user->name . ' 加入了对话'),
            $session,
            $request
        );
        $socket->to($room->session)->emit($response->event, $response->toEmit());

        $protocal = new JoinedRoom(['scene' => $room->scene, 'session' => $room->session]);
        $request->makeResponse($protocal)->emit($socket);

        return [];
    }


}