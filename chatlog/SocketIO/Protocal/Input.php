<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatlog\SocketIO\Messages\Message;
use Commune\Support\Message\AbsMessage;

/**
 *
 * @property-read string $session
 * @property-read string $scene
 * @property-read bool $bot
 * @property-read Message $message
 */
class Input extends AbsMessage
{
    public static function stub(): array
    {
        return [
            'session' => '',
            'scene' => '',
            'bot' => true,
            'message' => [],
        ];
    }

    public function __set_message(string $name, array $value) : void
    {
        $this->_data[$name] = Message::create($value);
    }

    public static function relations(): array
    {
        return [
            'message' => Message::class,
        ];
    }

    public function isEmpty(): bool
    {
        return false;
    }


}