<?php


namespace Commune\Chatlog;


use Commune\Support\Option\AbsOption;

/**
 * @property-read string $jwtSecret
 */
class ChatlogConfig extends AbsOption
{
    public static function stub(): array
    {
        return [

            'jwtSecret' => env('CHATLOG_JWT_SECRET', 'helple~~ss'),
        ];
    }

    public static function relations(): array
    {
        return [];
    }


}