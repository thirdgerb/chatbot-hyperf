<?php


namespace Commune\Chatlog\SocketIO\Protocal;


/**
 * @property-read string $scene
 * @property-read string $room
 */
class JoinedRoom extends ChatlogResProtocal
{
    public static function stub(): array
    {
        return [
            'scene' => '',
            'session' => '',
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
        return 'JOINED_ROOM';
    }


}