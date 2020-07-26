<?php


namespace Commune\Chatlog\SocketIO\Protocal;


/**
 * @property-read string $session
 * @property-read string $reason
 */
class ChatDelete extends ChatlogResProtocal
{
    public static function stub(): array
    {
        return [
            'session' => '',
            'reason' => '',
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function getEvent(): string
    {
        return 'CHAT_DELETE';
    }


}