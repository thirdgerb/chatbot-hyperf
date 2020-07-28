<?php


namespace Commune\Chatlog\SocketIO\Handlers;

use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\RoomProtocalPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\DTO\RoomInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Protocal\UserChats;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;


/**
 * 转人工服务
 */
class ManualHandler extends ChatlogEventHandler
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
         * @var RoomInfo $roomInfo
         */
        $roomInfo = $request->getTemp(RoomInfo::class);

        // 权限校验.
        $roomService = $this->getRoomService();
        $roomOption = $roomService->findRoom($roomInfo->scene);
        $chatInfo = $roomService->createChatInfo($roomOption, $user,  true);

        $protocal = new UserChats(['chats' => [$chatInfo]]);
        $response = $request->makeResponse($protocal);
        $superviseSession = $roomService->getSupervisorSession();

        $socket->to($superviseSession)
            ->emit($response->event, $response->toEmit());

        return [];
    }


}