<?php


namespace Commune\Chatbot\Hyperf\Providers;


use Commune\Blueprint\Configs\GhostConfig;
use Commune\Chatbot\Hyperf\Coms\HeedFallback\IFallbackSceneRepository;
use Commune\Container\ContainerContract;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Contracts\ServiceProvider;
use Hyperf\Redis\RedisFactory;

/**
 * @property-read string $poolName
 */
class HFHeedFallbackRepoProvider extends ServiceProvider
{
    public function getDefaultScope(): string
    {
        return self::SCOPE_PROC;
    }

    public static function stub(): array
    {
        return [
            'poolName' => 'default',
        ];
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->singleton(
            IFallbackSceneRepository::class,
            function(ContainerContract $app) {

                $factory = $app->make(RedisFactory::class);
                $reporter = $app->make(ExceptionReporter::class);
                /**
                 * @var GhostConfig $config
                 */
                $config = $app->make(GhostConfig::class);

                return new IFallbackSceneRepository(
                    $factory,
                    $reporter,
                    $config->id,
                    $this->poolName
                );
            }
        );
    }


}