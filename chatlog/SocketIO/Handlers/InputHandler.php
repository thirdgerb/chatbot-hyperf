<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Coms\EmitterAdapter;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\RoomProtocalPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class InputHandler extends ChatlogEventHandler
{
    protected $middlewares =[
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
        $input = InputInfo::create($request->proto);

        $scene = $input->scene;
        $roomService = $this->getRoomService();
        $room = $roomService->findRoom($scene);

        // 准备要发送的消息.
        $inputBatch = $savingBatch = MessageBatch::fromInput($input, $user);
        $emitter = new EmitterAdapter($socket);

        // 按房间的规则, 检查输入消息是否投递给指定对象, 通知其他人等等.
        $onInput = $roomService->onInput($room);
        if (isset($onInput)) {
            $inputBatch = $onInput(
                $request,
                $inputBatch,
                $user,
                $emitter
            );
        }

        // onInput 环节可以通过返回 null, 终止后续操作.
        if (empty($inputBatch)) {
            return [];
        }

        // 保存消息.
        $this->getMessageRepo()->saveBatch(
            $this->shell->getId(),
            $savingBatch
        );

        // 如果发送给机器人.
        if ($input->bot === true) {
            $this->deliverToChatbot(
                $request,
                $room,
                $user,
                $inputBatch,
                new EmitterAdapter($controller)
            );
        }

        return [];
    }


}