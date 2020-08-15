<?php


namespace Commune\Chatlog\SocketIO\Messages;

use Commune\Protocals\HostMsg;

class BiliMessage extends ChatlogMessage
{

    public static function stub(): array
    {
        return [
            'id' => '',
            'type' => ChatlogMessage::MESSAGE_BILI,
            'resource' => '',
            'text' => '',
            'level' => HostMsg::INFO,
        ];
    }

}