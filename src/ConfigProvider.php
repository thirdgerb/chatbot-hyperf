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

            'publish' => [
                [
                    'id' => 'memories table',
                    'description' => 'migrations for memories',
                    'source' => __DIR__ . '/../publish/migraitons/2020_07_05_0_create_memories_table.php',
                    'destination' => BASE_PATH . '/migrations/2020_07_05_0_create_memories_table.php'
                ],
                [
                    'id' => 'messages table',
                    'description' => 'migration for messages',
                    'source' => __DIR__ . '/../publish/migraitons/2020_07_05_0_create_messages_table.php',
                    'destination' => BASE_PATH . '/migrations/2020_07_05_0_create_messages_table.php'
                ],
                [
                    'id' => 'options table',
                    'description' => 'migration for options',
                    'source' => __DIR__ . '/../publish/migraitons/2020_07_05_0_create_options_table.php',
                    'destination' => BASE_PATH . '/migrations/2020_07_05_0_create_options_table.php'
                ],
            ],
        ];
    }

}