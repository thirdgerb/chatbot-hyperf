<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Support\Message\AbsMessage;

/**
 *
 * @property-read string $session
 * @property-read string $scene
 * @property-read int $createdAt
 * @property-read bool $bot
 * @property-read ChatlogMessage $message
 */
class Input extends AbsMessage
{
    public static function stub(): array
    {
        return [
            'session' => '',
            'scene' => '',
            'bot' => true,
            'createdAt' => 0,
            'message' => [],
        ];
    }

    public function __set_message(string $name, array $value) : void
    {
        $this->_data[$name] = ChatlogMessage::create($value);
    }

    public static function relations(): array
    {
        return [
            'message' => ChatlogMessage::class,
        ];
    }

    public function isEmpty(): bool
    {
        return false;
    }


}