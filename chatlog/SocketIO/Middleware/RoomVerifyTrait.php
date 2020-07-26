<?php


namespace Commune\Chatlog\SocketIO\Middleware;


use Commune\Chatbot\Hyperf\Coms\SocketIO\ProtocalException;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\Handlers\ChatlogEventHandler;
use Commune\Chatlog\SocketIO\Protocal\ChatDelete;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\RoomInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Support\Struct\InvalidStructException;
use Hyperf\SocketIOServer\Socket;

trait RoomVerifyTrait
{

    public function verifyRoom(
        string $scene,
        string $session,
        RoomService $service,
        ChatlogSioRequest $request,
        Socket $socket
    ) : ? array
    {

        try {
            $room = new RoomInfo(['scene' => $scene, 'session' => $session]);
        } catch (InvalidStructException $e) {
            throw new ProtocalException('invalid room data', $e);
        }

        $option = $service->findRoom($room->scene);
        if (empty($option)) {

            $chatDelete = new ChatDelete([
                'session' => $session,
                'reason' => '会话已经不存在',
            ]);

            $request->makeResponse($chatDelete)->emit($socket);
            return [];
        }

        $request->with(RoomInfo::class, $room);

        $user = $request->getTemp(UserInfo::class);
        if (empty($user)) {
            return null;
        }

        if (!$service->verifyUser($room->scene, $user, $room->session)) {
            return ChatlogEventHandler::forbidden(
                "没有权限访问当前房间",
                $request,
                $socket
            );
        }

        return null;
    }

}