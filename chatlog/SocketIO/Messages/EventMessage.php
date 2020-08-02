<?php


namespace Commune\Chatlog\SocketIO\Messages;


/**
 * @property-read string $name
 * @property-read array $payload
 * @property-read string $id
 * @property-read string $type
 */
class EventMessage extends ChatlogMessage
{

    public static function instance(string $name, array $payload = []) : self
    {
        return new static([
            'name' => $name,
            'payload' => $payload
        ]);
    }

    public static function stub(): array
    {
        return [
            'id' => '',
            'type' => ChatlogMessage::MESSAGE_EVENT,
            'name' => '',
            'payload' => [],
        ];
    }

    public function shouldSave(): bool
    {
        return false;
    }

}