<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Coms\EmitterAdapter;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\Messages\EventMessage;
use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\RoomProtocalPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\DTO\RoomInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Protocal\JoinedRoom;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Protocals\HostMsg\Convo\EventMsg;
use Commune\Protocals\HostMsg\DefaultEvents;
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

        // 告知用户自己.
        $protocal = new JoinedRoom(['scene' => $room->scene, 'session' => $room->session]);
        $request->makeResponse($protocal)->emit($socket);

        // 发送消息给服务端.
        $roomOption = $this->getRoomService()->findRoom($room->scene);

        if ($room->bot === true) {
            $event = EventMessage::instance(DefaultEvents::EVENT_CLIENT_CONNECTION);
            $newInput = new InputInfo([
                'session' => $room->session,
                'scene' => $room->scene,
                'bot' => true,
                'message' => $event,
            ]);

            $joinBatch = MessageBatch::fromInput($newInput, $user);

            $this->deliverToChatbot(
                $request,
                $roomOption,
                $user,
                $joinBatch,
                new EmitterAdapter($controller)
            );
        }

        return [];
    }


}