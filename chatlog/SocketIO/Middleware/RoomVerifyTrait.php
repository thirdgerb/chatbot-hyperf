<?php


namespace Commune\Chatlog\SocketIO\Middleware;


use Commune\Chatbot\Hyperf\Coms\SocketIO\ProtocalException;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\Handlers\ChatlogEventHandler;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\Room;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
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
            $room = new Room(['scene' => $scene, 'session' => $session]);
        } catch (InvalidStructException $e) {
            throw new ProtocalException('invalid room data', $e);
        }

        $option = $service->findRoom($room->scene);
        if (empty($option)) {
            return ChatlogEventHandler::makeUserQuitChat(
                $room->session,
                '房间已经不存在',
                $request,
                $this->socket
            );
        }

        $user = $request->getTemp(UserInfo::class);
        if (empty($user)) {
            return null;
        }

        if (!$service->verifyUser($room->scene, $user, $room->session)) {
            return ChatlogEventHandler::forbidden(
                $request,
                $socket
            );
        }




        return null;
    }

}