<?php

namespace Commune\Chatlog\Shell;


use Commune\Shell\IShellConfig;

class ChatlogShellConfig extends IShellConfig
{

    public static function stub(): array
    {
        return [
            'id' => '',
            'name' => '',

            'providers' => [],
            'options' => [],
            'components' => [],

            'protocals' => [
            ],
            'sessionExpire' => 864000,
        ];
    }

}