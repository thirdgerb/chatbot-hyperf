<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;

/**
 * 房间接受到来自机器人的 output 消息.
 */
interface OnOutput
{

    public function __invoke(
        RoomOption $room,
        ChatlogWebPacker $packer,
        MessageBatch $batch
    ) : ? MessageBatch;

}