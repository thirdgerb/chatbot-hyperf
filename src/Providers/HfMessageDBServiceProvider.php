<?php


/**
 * Class MessageDBByHfProvider
 * @package Commune\Chatbot\Hyperf\Providers
 */

namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Contracts\Cache;
use Commune\Contracts\Messenger\MessageDB;
use Commune\Contracts\ServiceProvider;
use Psr\Log\LoggerInterface;
use Commune\Chatbot\Hyperf\Coms\MessageDB\HfMysqlMessageDB;
use Commune\Chatbot\Hyperf\Coms\Database\MessageRepository;
use Commune\Container\ContainerContract;

/**
 * 基于 Hyperf DB 模块实现的消息仓库.
 *
 * @property-read string $connection,
 * @property-read string $tableName,
 * @property-read int $cacheExpire
 */
class HfMessageDBServiceProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_PROC;
    }

    public static function stub(): array
    {
        return [
            'connection' => 'default',
            'tableName' => MessageRepository::TABLE_NAME,
            'cacheExpire' => 10,
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            MessageDB::class,
            function(ContainerContract $app) {
                return new HfMysqlMessageDB(
                    $app->make(Cache::class),
                    $app->make(LoggerInterface::class),
                    $this->connection,
                    $this->tableName,
                    $this->cacheExpire
                );
            }

        );
    }


}