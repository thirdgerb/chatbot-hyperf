<?php


namespace Commune\Chatlog\SocketIO\Handlers;

use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\ChatInfo;
use Commune\Chatlog\SocketIO\Protocal\Room;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;


/**
 * 转人工服务
 */
class ManualHandler extends AbsChatlogEventHandler
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
        $user = $request->getTemp(UserInfo::class);
        if (empty($user)) {
            return [static::class => 'user is empty'];
        }

        $room = new Room($request->proto);
        $chatInfo = ChatInfo::createByUserRoom($room, $user);
        $response = $request->makeResponse($chatInfo);

        // todo 指定管理员房间.
        // 广播房间事件给各方.
        $socket->to('commune-chat')->emit($response->event, $response->toEmit());
        return [];
    }


}