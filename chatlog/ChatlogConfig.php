<?php


namespace Commune\Chatlog;


use Commune\Chatlog\SocketIO\Handlers;
use Commune\Support\Option\AbsOption;
use Lcobucci\JWT\Signer\Hmac\Sha256;

/**
 *
 * @property-read string $appName
 * @property-read string $jwtSigner
 * @property-read string $jwtSecret
 * @property-read int $jwtExpire   seconds
 * @property-read string[] $protocals
 */
class ChatlogConfig extends AbsOption
{
    public static function stub(): array
    {
        return [
            'appName' => 'chatlog',
            'protocals' => [
                'SIGN' => Handlers\SignHandler::class,
                'JOIN' => Handlers\JoinHandler::class,
                'LEAVE' => Handlers\LeaveHandler::class,
                'INPUT' => Handlers\InputHandler::class,
            ],
            'jwtSigner' => Sha256::class,
            'jwtSecret' => env('CHATLOG_JWT_SECRET', 'helple~~ss'),
            'jwtExpire' => 86400 * 7,
        ];
    }

    public static function relations(): array
    {
        return [];
    }


}