<?php


namespace Commune\Chatlog\SocketIO\Protocal;


/**
 * @property-read string $session
 * @property-read int $limit
 * @property-read bool $forward
 * @property-read MessageBatch[] $batches
 */
class MessagesMerge extends ChatlogResProtocal
{
    public static function stub(): array
    {
        return [
            'session' => '',
            'limit' => 0,
            'batches' => [],
            'forward' => true,
        ];
    }

    public static function relations(): array
    {
        return [
            'batches[]' => MessageBatch::class,
        ];
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function getEvent(): string
    {
        return 'MESSAGES_MERGE';
    }


}