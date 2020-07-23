<?php


namespace Commune\Chatlog\SocketIO\Handlers;

use Commune\Chatbot\Hyperf\Coms\SocketIO\EventHandler;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\ChatInfo;
use Commune\Chatlog\SocketIO\Protocal\Room;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\SioResponse;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;


/**
 * 转人工服务
 */
class ManualHandler extends EventHandler
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
        $user = $request->user;
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