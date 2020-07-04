<?php


/**
 * Class BroadcastByHfRedisProvider
 * @package Commune\Chatbot\Hyperf\Services
 */

namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Container\ContainerContract;
use Commune\Contracts\Messenger\Broadcaster;
use Commune\Contracts\ServiceProvider;
use Hyperf\Redis\Pool\PoolFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Log\LoggerInterface;
use Commune\Chatbot\Hyperf\Coms\Broadcaster\HfRedisBroadcaster;

/**
 * 基于 Hyperf 的 Redis 连接池, 以及 Redis 的广播来实现的广播模块.
 *
 * @property-read string $redis             使用 Hyperf 的哪个 Redis 连接池.
 * @property-read string[] $listeningShells 每条消息都会广播到的目标 Shell. 通常是管理员 Shell.
 */
class BroadcastByHfRedisProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_PROC;
    }

    public static function stub(): array
    {
        return [
            'redis' => 'default',
            'listeningShells' => [],
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            Broadcaster::class,
            function(ContainerContract $app) {

                $container = ApplicationContext::getContainer();

                $poolFactory = $container->get(PoolFactory::class);
                $redisFactory = $container->get(RedisFactory::class);

                $logger = $app->get(LoggerInterface::class);

                return new HfRedisBroadcaster(
                    $poolFactory,
                    $redisFactory,
                    $logger,
                    $this->redis,
                    $this->listeningShells
                );
            }
        );
    }


}