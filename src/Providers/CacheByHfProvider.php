<?php


/**
 * Class CacheByHfProvider
 * @package Commune\Chatbot\Hyperf\Providers
 */

namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Chatbot\Hyperf\Coms\Cache\HfRedisCache;
use Commune\Container\ContainerContract;
use Commune\Contracts\ServiceProvider;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

/**
 *
 * @property-read string $redis     所使用的 Hyperf redis 连接.
 * @property-read string $prefix    所有缓存 key 的前缀.
 */
class CacheByHfProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_PROC;
    }

    public static function stub(): array
    {
        return [
            'redis' => 'default',
            'prefix' => 'hf',
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            ContainerContract::class,
            function (ContainerContract $app) {

                $factory = ApplicationContext::getContainer()->get(RedisFactory::class);

                return new HfRedisCache(
                    $factory,
                    $this->redis,
                    $this->prefix
                );
            }
        );
    }


}