<?php


/**
 * Class RuntimeDriverByHfProvider
 * @package Commune\Chatbot\Hyperf\Providers
 */

namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Blueprint\Ghost\Cloner\ClonerLogger;
use Commune\Chatbot\Hyperf\Coms\Database\MemoryRepository;
use Commune\Chatbot\Hyperf\Coms\Runtime\HfRuntimeDriver;
use Commune\Container\ContainerContract;
use Commune\Contracts\Cache;
use Commune\Contracts\Ghost\RuntimeDriver;
use Commune\Contracts\ServiceProvider;

/**
 * @property-read string $connection        所使用的 hyperf 数据库连接.
 * @property-read string $tableName         所使用的 hyperf 数据表名. 通常是 mysql
 * @property-read int $longTermMemoryTtl
 */
class HfRuntimeDriverProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_REQ;
    }

    public static function stub(): array
    {
        return [
            'connection' => 'default',
            'tableName' => MemoryRepository::TABLE_NAME,
            'longTermMemoryTtl' => 60,
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            RuntimeDriver::class,
            function(ContainerContract $app) {

                $cache = $app->make(Cache::class);
                $logger = $app->make(ClonerLogger::class);

                return new HfRuntimeDriver(
                    $cache,
                    $logger,
                    $this->connection,
                    $this->tableName,
                    $this->longTermMemoryTtl
                );
            }
        );
    }


}