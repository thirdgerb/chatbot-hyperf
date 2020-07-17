<?php

namespace Commune\Chatbot\Hyperf\Providers;


use Hyperf;
use Commune\Container\ContainerContract;
use Commune\Contracts\ServiceProvider;
use Psr\EventDispatcher\EventDispatcherInterface;
use Hyperf\Utils\ApplicationContext;

/**
 * 将 Hyperf 容器里的对象预注册到 Commune 容器
 *
 * @property-read string[] $singletons
 */
class HyperfDIBridgeServiceProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_CONFIG;
    }

    public static function stub(): array
    {
        return [

            'singletons' => [
                // redis 工厂.
                Hyperf\Redis\RedisFactory::class,
                // server 端.
                Hyperf\Server\ServerFactory::class,
                // hyperf 的配置中心.
                Hyperf\Contract\ConfigInterface::class,
                // hyperf 自己的控制台.
                Hyperf\Contract\StdoutLoggerInterface::class,
                // 事件
                EventDispatcherInterface::class,
            ],
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        foreach ($this->singletons as $singleton) {
            $app->singleton(
                $singleton,
                function() use ($singleton){
                    return ApplicationContext::getContainer()->get($singleton);
                }
            );
        }
    }


}