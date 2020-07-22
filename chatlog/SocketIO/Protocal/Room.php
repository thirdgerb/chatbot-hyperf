<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Support\Message\AbsMessage;

/**
 * @property-read string $session
 * @property-read string $scene
 */
class Room extends AbsMessage
{
    public static function stub(): array
    {
        return [
            'session' => '',
            'scene' => '',
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        if (empty($data['session'])) {
            return "session should not be empty";
        }
        return parent::validate($data);
    }

    public function isEmpty(): bool
    {
        return false;
    }


}