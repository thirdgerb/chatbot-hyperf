<?php

namespace Commune\Chatbot\Hyperf;


use Commune\Chatbot\Hyperf\Command\StartAppCommand;

class ConfigProvider
{

    public function __invoke() : array
    {
        return [
            'commands' => [
                StartAppCommand::class,
            ],

        ];
    }

}