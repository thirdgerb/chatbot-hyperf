<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\EmitterAdapter;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Handlers\ChatlogEventHandler;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\UserChats;

class DefaultOnInput implements OnInput
{

    /**
     * @var RoomService
     */
    protected $roomService;

    /**
     * OnInputDefault constructor.
     * @param RoomService $roomService
     */
    public function __construct(RoomService $roomService)
    {
        $this->roomService = $roomService;
    }

    public function __invoke(
        ChatlogSioRequest $request,
        MessageBatch $input,
        UserInfo $user,
        EmitterAdapter $emitter
    ): ? MessageBatch
    {
        $scene = $input->scene;

        // 广播给群里的其它用户.
        if ($input->shouldSave()) {
            ChatlogEventHandler::broadcastBatch(
                $request->trace,
                $input,
                $emitter
            );
        }

        // 如果是监控中的场景, 通知管理员.
        // 这个环节是 information, 可以独立成模块.
        if (
            $input->shouldSave()
            && $this->roomService->isRoomSupervised($scene)
            && !$this->roomService->isSupervisor($user)
        ) {
            $room = $this->roomService->findRoom($scene);
            $chatInfo = $this->roomService->createChatInfo(
                $room,
                $user,
                true
            );

            $protocal = new UserChats(['chats' => [$chatInfo]]);
            $response = $request->makeResponse($protocal);
            $session = $this->roomService->getSupervisorSession();
            $emitter
                ->to($session)
                ->emit($response->event, $response->toEmit());
        }

        return $input;
    }


}