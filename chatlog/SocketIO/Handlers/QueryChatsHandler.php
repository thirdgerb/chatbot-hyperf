<?php

namespace Commune\Chatlog\SocketIO\Handlers;

use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\DTO\ChatInfo;
use Commune\Chatlog\SocketIO\DTO\QueryChatsInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\UserChats;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

/**
 * 客户端请求会话.
 */
class QueryChatsHandler extends ChatlogEventHandler
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
        $query = new QueryChatsInfo($request->proto);
        $user = $request->getTemp(UserInfo::class);

        $service = $this->getRoomService();

        $rooms = $service->roomsFor($user);
        $all = $query->all;
        if (!$all) {
            $rooms = array_filter($rooms, function(RoomOption $room) {
                return $room->autoJoin || $room->recommend;
            });
        }

        $cate = $query->category;
        if ($cate) {
            $rooms = array_filter($rooms, function(RoomOption $room) use ($cate) {
               return $room->category === $cate;
            });
        }

        if (empty($rooms)) {
            return [];
        }

        $chats = array_map(function(RoomOption $room) use ($service, $user){
            return $service->createChatInfo($room, $user, false);
        }, $rooms);

        $protocal = new UserChats([
            'category' => $cate,
            'all' => $all,
            'chats' => $chats,
            'init' => true,
        ]);

        $request->makeResponse($protocal)->emit($socket);
        return [];
    }


}