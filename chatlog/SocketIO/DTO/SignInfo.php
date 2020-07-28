<?php


namespace Commune\Chatlog\SocketIO\DTO;

use Commune\Support\Message\AbsMessage;

/**
 * @property-read string $name
 * @property-read string $password
 */
class SignInfo extends AbsMessage
{
    const MAX_PASS_WD_LENGTH = 18;
    const MIN_PASS_WD_LENGTH = 4;
    const MAX_NAME_LENGTH = 10;

    public static function stub(): array
    {
        return [
            'name' => '',
            'password' => '',
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public function isEmpty(): bool
    {
        return empty($this->_data['name']) && empty($this->_data['password']);
    }


}