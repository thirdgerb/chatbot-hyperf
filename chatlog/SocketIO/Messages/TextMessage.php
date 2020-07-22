<?php


namespace Commune\Chatlog\SocketIO\Messages;

use Commune\Message\Host\Convo\IText;
use Commune\Protocals\HostMsg;


/**
 * @property-read string $id
 * @property-read string $type
 * @property-read string $text
 */
class TextMessage extends Message
{

    public static function instance(string $text) : TextMessage
    {
        return new static(['text' => $text]);
    }

    public static function stub(): array
    {
        return [
            'id' => '',
            'type' => Message::MESSAGE_TEXT,
            'text' => '',
        ];
    }

    public function toHostMsg() : HostMsg
    {
        return IText::instance($this->text);
    }
}