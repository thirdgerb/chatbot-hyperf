<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\EmitterAdapter;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;

/**
 * 房间接受到来自用户的 input 消息
 */
interface OnInput
{

    public function __invoke(
        ChatlogSioRequest $request,
        MessageBatch $input,
        UserInfo $user,
        EmitterAdapter $emitter
    ): ? MessageBatch;

}