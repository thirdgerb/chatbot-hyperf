<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;

/**
 * platform 打包 packer 时使用
 */
interface OnPacker
{

    public function __invoke(RoomOption $room, ChatlogWebPacker $packer) : ? ChatlogWebPacker;

}