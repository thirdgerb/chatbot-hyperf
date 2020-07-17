<?php


namespace Commune\Chatbot\Hyperf\Foundation;


use Commune\Blueprint\Configs\HostConfig;
use Commune\Blueprint\Framework\ProcContainer;
use Commune\Blueprint\Host;
use Commune\Chatbot\Hyperf\Coms\Console\StdoutConsoleLogger;
use Commune\Host\IHost;
use Commune\Host\IHostConfig;
use Commune\Support\Utils\StringUtils;
use Commune\Support\Utils\TypeUtils;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Container;
use Psr\Container\ContainerInterface;

/**
 * 从 Hyperf 中获取 Host
 */
class HostFactory
{

    public function __invoke(ContainerInterface $container) : Host
    {
        $procContainer = $this->prepareContainer($container);
        $hfConsole = $container->get(StdoutLoggerInterface::class);

        $console = new StdoutConsoleLogger($hfConsole);
        $config = $container->get(HostConfig::class);

        $host = new IHost(
            $config,
            $procContainer,
            null,
            null,
            $console
        );

        return $host;
    }



    protected function prepareContainer(ContainerInterface $container) : ProcContainer
    {
        if (!$container instanceof Container) {
            throw new \InvalidArgumentException(
                Container::class
                . 'expected, '
                . TypeUtils::getType($container)
                . ' given'
            );
        }
        return new HfProcessContainer($container);
    }
}