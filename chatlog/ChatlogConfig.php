<?php


namespace Commune\Chatlog;


use Commune\Support\Option\AbsOption;
use Lcobucci\JWT\Signer\Hmac\Sha256;

/**
 *
 * @property-read string $appName
 * @property-read string $jwtSigner
 * @property-read string $jwtSecret
 * @property-read int $jwtExpire   seconds
 */
class ChatlogConfig extends AbsOption
{
    public static function stub(): array
    {
        return [
            'appName' => 'chatlog',
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