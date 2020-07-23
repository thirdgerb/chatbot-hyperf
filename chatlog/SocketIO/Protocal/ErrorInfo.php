<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Blueprint\Exceptions\CommuneErrorCode;

class ErrorInfo extends ChatlogResProtocal implements CommuneErrorCode
{
    public static function stub(): array
    {
        return [
            'errcode' => 0,
            'errmsg' => '',
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
        return 'ERROR_INFO';
    }


}