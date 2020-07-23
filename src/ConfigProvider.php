<?php

namespace Commune\Chatbot\Hyperf;

use Commune\Chatbot\Hyperf\Command\StartAppCommand;

class ConfigProvider
{
    protected $migrations = [
        'memories' => '2020_07_05_0_create_memories_table.php',
        'messages' => '2020_07_05_0_create_messages_table.php',
        'options' => '2020_07_05_0_create_options_table.php',
        'chatlog_messages' => '2020_07_05_0_create_chatlog_messages_table.php',
        'chatlog_users' => '2020_07_05_0_create_chatlog_users_table.php',
    ];

    public function __invoke() : array
    {
        return [
            'commands' => [
                StartAppCommand::class,
            ],
            'dependencies' => [
            ],
            'publish' => $this->migrations,
        ];
    }

    protected function getMigrations(): array
    {

        $migrations = [];
        foreach ($this->migrations as $name => $file) {
            $migrations[] = [
                'id' => "$name table",
                'description' => "migration for $name",
                'source' => __DIR__ . "/../publish/migraitons/$file",
                'destination' => BASE_PATH . "/migrations/$file",

            ];
        }

        return $migrations;
    }

}