<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;

class DefaultOnPacker implements OnPacker
{
    public function __invoke(RoomOption $room, ChatlogWebPacker $packer) : ? ChatlogWebPacker
    {
        $entry = $room->entry;
        if ($entry) {
            $packer->entry = $entry;
        }

        return $packer;
    }


}