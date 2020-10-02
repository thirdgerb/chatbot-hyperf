<?php


namespace Commune\Chatbot\Hyperf\Platforms\Http;


use Commune\Chatbot\Hyperf\Servers\AbsHyperfServerPlatform;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Router;

/**
 * Hyperf Http 协议端的实现.
 */
class HfHttpPlatform extends AbsHyperfServerPlatform
{
    /**
     * @var HfHttpConfig
     */
    protected $httpConfig;

    protected function initializeHyperf(): void
    {
        $httpConfig = $this->getHttpConfig();
        $factory = $this->container->get(DispatcherFactory::class);
        Router::init($factory);

        $routes = $httpConfig->routes;
        if (empty($routes)) {
            return;
        }
        $console = $this->host->getConsoleLogger();

        foreach ($httpConfig->servers as $server) {
            $name = $server->name;
            Router::addServer($name, function() use ($routes, $console) {
                foreach ($routes as $route) {
                    if (file_exists($route)) {
                        require_once $route;
                    } else {
                        $console->error("route file $route not exists");
                    }
                }
            });
        }
    }

    public function getHyperfPlatformOption(): HfPlatformOption
    {
        return $this->getHttpConfig()->toHyperfPlatformOption();
    }

    public function getHttpConfig() : HfHttpConfig
    {
        return $this->httpConfig
            ?? $this->httpConfig = $this
            ->host
            ->getProcContainer()
            ->make(HfHttpConfig::class);
    }


}