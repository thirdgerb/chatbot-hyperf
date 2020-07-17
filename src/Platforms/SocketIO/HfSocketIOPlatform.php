<?php

namespace Commune\Chatbot\Hyperf\Platforms\SocketIO;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
use Hyperf\SocketIOServer\Collector\SocketIORouter;
use Hyperf\SocketIOServer\Room\AdapterInterface;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Commune\Chatbot\Hyperf\Servers\AbsHyperfServerPlatform;
use Commune\Chatbot\Hyperf\Servers\HfPlatformOption;

/**
 * Hyperf 的 Socket.io 端
 */
class HfSocketIOPlatform extends AbsHyperfServerPlatform
{
    /**
     * @var null|HfSocketIOOption
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


        // 设置路由.
        $routes = $option->routes;
        foreach ($routes as $namespace => $controller) {
            SocketIORouter::addNamespace(
                $namespace,
                $controller
            );
        }
    }

    public function getSocketIOOption() : HfSocketIOOption
    {
        return $this->option
            ?? $this->option = $this->host
                ->getProcContainer()
                ->make(HfSocketIOOption::class);
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