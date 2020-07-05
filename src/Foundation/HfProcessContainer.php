<?php

/**
 * Class ProcessContainer
 * @package Commune\Hyperf\Foundations
 */

namespace Commune\Chatbot\Hyperf\Foundation;

use Hyperf\Di\Container;
use Commune\Blueprint\Framework\ProcContainer;
use Commune\Container\ContainerContract;
use Hyperf\Utils\ApplicationContext;

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

    public function __construct(Container $container, array $bindings = [])
    {
        // Hyperf Container 不好依赖注入. 用静态方法获取又依赖太重.
        // 所以直接用 ContainerInterface 替代
        $this->instance(Container::class, $container);

        // 分享 sharing
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
}