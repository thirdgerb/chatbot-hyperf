<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;

interface OnOutput
{

    public function __invoke(
        RoomOption $room,
        ChatlogWebPacker $packer,
        MessageBatch $batch
    ) : ? MessageBatch;

}