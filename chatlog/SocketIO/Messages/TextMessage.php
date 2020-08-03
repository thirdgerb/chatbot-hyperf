<?php


namespace Commune\Chatlog\SocketIO\Messages;

/**
 * @property-read string $id
 * @property-read string $type
 * @property-read string $text
 * @property-read bool $md
 */
class TextMessage extends ChatlogMessage
{

    public static function instance(string $text) : TextMessage
    {
        return new static(['text' => $text]);
    }

    public static function stub(): array
    {
        return [
            'id' => '',
            'type' => ChatlogMessage::MESSAGE_TEXT,
            'text' => '',
            'md' => false,
        ];
    }

}