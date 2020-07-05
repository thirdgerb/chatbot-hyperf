<?php


/**
 * Class OptRegistryByDBProvider
 * @package Commune\Chatbot\Hyperf\Providers
 */

namespace Commune\Chatbot\Hyperf\Providers;

use Commune\Chatbot\Hyperf\Coms\Database\OptionRepository;
use Commune\Chatbot\Hyperf\Coms\Storage\HfDBStorageDriver;
use Commune\Container\ContainerContract;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Contracts\ServiceProvider;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;

/**
 * 基于 Hyperf DB 系统实现的配置中心抽象层数据仓库.
 *
 * @property-read string $connection    所使用的 Hyperf DB 的连接名.
 * @property-read string $tableName     所使用的数据表名称. 基于 mysql 的话.
 * @property-read string $redis         所使用的 redis 配置.
 */
class HfOptionStorageServiceProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_CONFIG;
    }

    public static function stub(): array
    {
        return [
            'connection' => 'default',
            'tableName' => OptionRepository::TABLE_NAME,
            'redis' => 'default',
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            HfDBStorageDriver::class,
            function(ContainerContract $app) {

                $redisFactory = ApplicationContext::getContainer()
                    ->get(RedisFactory::class);

                $logger = $app->make(LoggerInterface::class);
                $reporter = $app->make(ExceptionReporter::class);


                return new HfDBStorageDriver(
                    $redisFactory,
                    $logger,
                    $reporter,
                    $this->connection,
                    $this->tableName,
                    $this->redis
                );
            }
        );
    }


}