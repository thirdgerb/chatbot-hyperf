<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\DTO\QuerySceneInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\UserChats;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class QuerySceneHandler extends ChatlogEventHandler
{
    protected $middlewares =[
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
        $queryScene = new QuerySceneInfo($request->proto);
        $user = $request->getTemp(UserInfo::class);

        $service = $this->getRoomService();

        $scene = $queryScene->scene;
        $room = $service->findRoom($scene);
        if (empty($room)) {
            return static::emitErrorInfo(
               ErrorInfo::UNPROCESSABLE_ENTITY,
               "场景 [$scene] 未定义",
               $request,
               $socket
            );
        }

        if (!$service->verifyUser($scene, $user)) {
            return static::emitErrorInfo(
                ErrorInfo::FORBIDDEN,
                "无权限访问场景 [$scene]",
                $request,
                $socket
            );
        }

        $chat = $service->createChatInfo($room, $user);

        $protocal = new UserChats(['chats' => [$chat]]);

        $request->makeResponse($protocal)->emit($socket);
        return [];
    }


}