<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\DTO\RoomInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class LeaveHandler extends ChatlogEventHandler
{
    protected $middlewares = [
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
        AuthorizePipe::class,
    ];

    function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $room = new RoomInfo($request->proto);
        /**
         * @var UserInfo $user
         */
        $user = $request->getTemp(UserInfo::class);

        // 任何时候都允许退出房间.
        $session = $room->session;
        $socket->leave($session);

        // 如果用户本来无权限加入房间, 就有鬼了.
        if ($this->getRoomService()->verifyUser($room->scene, $user, $session)) {
            $this->logger->warning(
                'user ' . $user->toJson()
                . ' try to leave room ' . $room->toJson()
                . ' ta can not join'
            );
            return [];
        }

        // 通知其它用户有人退出登录.
        $text = TextMessage::instance($user->name . ' 离开了对话');
        $response = $request->makeResponse(
            MessageBatch::fromSystem($session, $text)
        );
        $socket->to($session)->emit($response->event, $response->toEmit());

        return [];
    }


}