<?php


/**
 * Class CacheByRedisFactoryProvider
 * @package Commune\Chatbot\Hyperf\Services
 */

namespace Commune\Chatbot\Hyperf\Coms;


use Commune\Chatbot\Hyperf\Coms\Cache\HfRedisCache;
use Commune\Container\ContainerContract;
use Commune\Contracts\Cache;
use Commune\Contracts\ServiceProvider;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

/**
 * Class CacheByRedisFactoryProvider
 *
 * 基于 Hyperf 的 RedisFactory 实现的 Cache.
 *
 * @property-read string $prefix    缓存的前缀.
 * @property-read string $poolName  使用 Hyperf 的哪一个 Redis pool
 */
class CacheByHfRedisProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_PROC;
    }

    public static function stub(): array
    {
        return [
            'prefix' => '',
            'poolName' => 'default',
        ];
    }

    public function getId(): string
    {
        return static::class . '::' . $this->prefix;
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            Cache::class,
            function (ContainerContract $app) {

                $factory = ApplicationContext::getContainer()
                    ->get(RedisFactory::class);

                return new HfRedisCache(
                    $factory,
                    $this->poolName,
                    $this->prefix
                );
            }
        );
    }


}