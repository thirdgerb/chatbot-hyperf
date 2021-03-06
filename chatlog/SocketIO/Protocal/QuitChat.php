<?php


namespace Commune\Chatlog\SocketIO\Protocal;


/**
 * @property-read string $session
 * @property-read string $reason
 */
class QuitChat extends ChatlogResProtocal
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
        return 'QUIT_CHAT';
    }


}