<?php


namespace Commune\Chatlog\SocketIO\Protocal;
use Commune\Chatlog\SocketIO\DTO\ChatInfo;


/**
 * @property-read string $category
 * @property-read bool $all
 * @property-read ChatInfo[] $chats
 */
class UserChats extends ChatlogResProtocal
{
    public static function stub(): array
    {
        return [
            'category' => '',
            'all' => false,
            'chats' => [],
        ];
    }

    public static function relations(): array
    {
        return [
            'chats[]' => ChatInfo::class,
        ];
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function getEvent(): string
    {
        return 'USER_CHATS';
    }


}