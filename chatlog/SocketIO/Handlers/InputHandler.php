<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\RoomVerifyTrait;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Protocal\UserChats;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class InputHandler extends ChatlogEventHandler
{
    use RoomVerifyTrait;

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
        $user = $request->getTemp(UserInfo::class);
        $input = InputInfo::create($request->proto);

        $scene = $input->scene;
        $session = $input->session;

        $roomService = $this->getRoomService();
        $errors = $this->verifyRoom(
            $scene,
            $session,
            $roomService,
            $request,
            $socket
        );

        if (!empty($errors)) {
            return $errors;
        }

        // 准备要发送的消息.
        $inputBatch = MessageBatch::fromInput($input, $user);

        // 广播消息给群里其他人.
        $response = $request->makeResponse($inputBatch);
        $socket->to($input->session)->emit($response->event, $response->toEmit());

        // 保存消息.
        $this->getMessageRepo()->saveBatch(
            $this->shell->getId(),
            $inputBatch
        );

        // 如果发送给机器人.
        if ($input->bot) {
            $this->deliverToChatbot($inputBatch, $request, $controller, $socket);
        }


        // 如果是监控中的场景, 通知管理员.
        if ($roomService->isSupervisorScene($scene)) {
            $chatInfo = $roomService->createChatInfo(
                $roomService->findRoom($scene),
                $user,
                true
            );

            $protocal = new UserChats(['chats' => $chatInfo]);
            $response = $request->makeResponse($protocal);
            $controller
                ->to($roomService->getSupervisorSession())
                ->emit($response->event, $response->toEmit());
        }

        return [];
    }


}