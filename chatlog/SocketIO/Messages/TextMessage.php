<?php


namespace Commune\Chatlog\SocketIO\Messages;

use Commune\Protocals\HostMsg;

/**
 * @property-read string $id
 * @property-read string $type
 * @property-read string $text
 * @property-read bool $md
 */
class TextMessage extends ChatlogMessage
{

    public static function instance(string $text, string $level = HostMsg::INFO) : TextMessage
    {
        return new static(['text' => $text, 'level' => $level]);
    }

    public static function stub(): array
    {
        return [
            'id' => '',
            'type' => ChatlogMessage::MESSAGE_TEXT,
            'text' => '',
            'level' => HostMsg::INFO,
            'md' => false,
        ];
    }

}