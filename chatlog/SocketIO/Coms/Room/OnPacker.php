<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;

interface OnPacker
{

    public function __invoke(RoomOption $room, ChatlogWebPacker $packer) : ? ChatlogWebPacker;

}