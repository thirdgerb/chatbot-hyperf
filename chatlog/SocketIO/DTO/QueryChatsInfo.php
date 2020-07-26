<?php


namespace Commune\Chatlog\SocketIO\DTO;


use Commune\Support\Message\AbsMessage;

/**
 * @property-read string $category
 * @property-read bool $all
 */
class QueryChatsInfo extends AbsMessage
{
    public static function stub(): array
    {
        return [
            'category' => '',
            'all' => false,
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


}