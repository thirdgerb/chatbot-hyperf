<?php


namespace Commune\Chatlog\SocketIO\Messages;


use Commune\Protocals\HostMsg;
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
            'level' => HostMsg::INFO,
        ];
    }

    public function __set_id(string $name, $value) : void
    {
        $value = empty($value)
            ? $this->createUuId()
            : $value;

        $this->_data[$name] = $value;
    }

    public static function createMessage(array $data = []): Struct
    {
        $type = $data['type'] ?? 'text';
        switch ($type) {
            case self::MESSAGE_TEXT:
                return TextMessage::create($data);
            case self::MESSAGE_BILI :
                return BiliMessage::create($data);
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

    public function shouldSave() : bool
    {
        return true;
    }


}