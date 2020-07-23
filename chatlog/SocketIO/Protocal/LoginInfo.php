<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Support\Message\AbsMessage;

/**
 * 用户已经登录.
 *
 * @property-read string $id
 * @property-read string $name
 * @property-read string $token
 */
class LoginInfo extends ResponseProtocal
{
    public function getEvent(): string
    {
        return 'LOGIN';
    }

    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',
            'token' => '',
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