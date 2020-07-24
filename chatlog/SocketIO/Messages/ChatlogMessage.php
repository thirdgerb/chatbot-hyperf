<?php


namespace Commune\Chatlog\SocketIO\Messages;


use Commune\Support\Message\AbsMessage;
use Commune\Support\Struct\Struct;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;

/**
 * @property-read string $id
 * @property-read string $type
 */
class ChatlogMessage extends AbsMessage implements HasIdGenerator
{
    use IdGeneratorHelper;

    const MESSAGE_TEXT = 'text';
    const MESSAGE_BILI = 'bili';
    const MESSAGE_EVENT = 'event';

    public static function stub(): array
    {
        return [
            'id' => '',
            'type' => '',
        ];
    }

    public function __set_id(string $name, $value) : void
    {
        $value = empty($value)
            ? $this->createUuId()
            : $value;

        $this->_data[$name] = $value;
    }

    public static function create(array $data = []): Struct
    {
        $type = $data['type'] ?? 'text';
        switch ($type) {
            case self::MESSAGE_TEXT:
                return new TextMessage($data);
            default :
                return parent::create($data);
        }
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