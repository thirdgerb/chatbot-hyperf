<?php

namespace Commune\Chatbot\Hyperf\Platforms\SocketIO;


use FastRoute\Dispatcher;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Commune\Chatbot\Hyperf\Hacks\SockeIO\HfSocketIO;
use Hyperf\SocketIOServer\Collector\SocketIORouter;
use Hyperf\SocketIOServer\Room\AdapterInterface;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Commune\Chatbot\Hyperf\Servers\AbsHyperfServerPlatform;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;

/**
 * Hyperf 的 Socket.io Platform 实现.
 */
class HfSocketIOPlatform extends AbsHyperfServerPlatform
{
    /**
     * @var null|HfSocketIOConfig
     */
    protected $option;

    /**
     * @var HfPlatformOption
     */
    protected $platformOption;

    /**
     * 初始化 SocketIO 的控制器.
     */
    protected function initializeHyperf(): void
    {
        // 容器.
        $container = ApplicationContext::getContainer();

        /**
         * @var ConfigInterface $config
         */
        $config = $container->get(ConfigInterface::class);

        // 初始化配置. 覆盖公共配置. 避免和 hyperf 默认的配置冲突.
        $option = $this->getSocketIOOption();
        $data = $config->get('dependencies', []);

        $data[AdapterInterface::class] = $option->roomProvider;
        $data[SidProviderInterface::class] = $option->sidProvider;

        $config->set('dependencies', $data);


        $path = $option->path;
        /**
         * @var DispatcherFactory $factory
         * @var Dispatcher $router
         */
        $factory = $container->get(DispatcherFactory::class);

        // 自动生成路由
        foreach ($option->servers as $serverOption) {
            $router = $factory->getRouter($serverOption->name);
            $router->addRoute('GET', $path, HfSocketIO::class);
        }

        // 添加 socket io 的命名空间.
        $controller = $option->controller;
        SocketIORouter::addNamespace('/', $controller);
        $namespaces = $option->namespaces;
        foreach ($namespaces as $nsp => $controller) {
            SocketIORouter::addNamespace($nsp, $controller);
        }
    }

    public function getSocketIOOption() : HfSocketIOConfig
    {
        return $this->option
            ?? $this->option = $this->host
                ->getProcContainer()
                ->make(HfSocketIOConfig::class);
    }

    public function getHyperfPlatformOption(): HfPlatformOption
    {
        if (isset($this->platformOption)) {
            return $this->platformOption;
        }

        $option = $this->getSocketIOOption();
        return $this->platformOption = $option->toHyperfPlatformOption();

    }


}