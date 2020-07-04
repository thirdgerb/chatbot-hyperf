<?php

/**
 * Class ProcessContainer
 * @package Commune\Hyperf\Foundations
 */

namespace Commune\Chatbot\Hyperf\Foundations;

use Commune\Blueprint\Framework\ProcContainer;
use Commune\Chatbot\Hyperf\Foundation\IContainer;
use Commune\Container\ContainerContract;
use Hyperf\Database\ConnectionResolverInterface;
use Hyperf\Guzzle\ClientFactory;
use Hyperf\Redis\RedisFactory;
use Hyperf\Utils\ApplicationContext;
use Psr\Container\ContainerInterface;

/**
 * 通过 hyperf 的容器生成 commune chatbot 的进程级容器.
 *
 * Hyperf 的进程级实例都可以使用,
 * 同时新的绑定关系在自己身上.
 *
 * 但严格来说两个容器还是分离的. 依赖注入可能发生传入容器不一致的奇怪现象.
 *
 * 最好还是在 Commune Service Provider 中使用 ApplicationContext 来获取 Hyperf 的实例.
 *
 * @see ApplicationContext
 */
class HfProcessContainer extends IContainer implements ProcContainer
{
    const HYPERF_CONTAINER_ID = 'hyperf.container';

    /**
     * @var ContainerInterface
     */
    protected $hfContainer;

    /**
     * @var string[]
     */
    protected $fromHyperf = [
        ClientFactory::class,
        RedisFactory::class,
        ConnectionResolverInterface::class,
    ];

    public function __construct(ContainerInterface $container, array $bindings = [])
    {
        $this->hfContainer = $container;

        // Hyperf Container 不好依赖注入. 用静态方法获取又依赖太重.
        // 所以直接用 ContainerInterface 替代
        $this->instance(ContainerInterface::class, $container);
        $this->instance(self::HYPERF_CONTAINER_ID, $container);

        // 分享 sharing
        $bindings = array_unique(array_merge($this->fromHyperf, $bindings));
        foreach ($bindings as $sharing) {
            $this->singleton(
                $sharing,
                function(ContainerContract $app) use ($sharing) {
                    $container = $app[self::HYPERF_CONTAINER_ID];
                    return $container->get($sharing);
                }
            );
        }
    }


    public function bound(string $abstract): bool
    {
        return parent::bound($abstract)
            || $this->hfContainer->has($abstract);
    }

    public function make(string $abstract, array $parameters = [])
    {
        // 如果已经绑定了, 使用自带的.
        if (parent::bound($abstract)) {
            return parent::make($abstract, $parameters);
        }

        // 否则尝试检查 hyperf 的容器.
        if ($this->hfContainer->has($abstract)) {
            $instance = $this->hfContainer->get($abstract);
            // 自己也持有.
            $this->instance($abstract, $instance);
        }

        // 都没有, 还是用自身好了, 自己抛出异常.
        return parent::make($abstract, $parameters);
    }

}