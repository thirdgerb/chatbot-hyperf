<?php

namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Container\ContainerContract;
use Commune\Contracts\ServiceProvider;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;

/**
 * 将 Hyperf 容器里的对象注册到 Commune 容器
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
                RedisFactory::class,
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