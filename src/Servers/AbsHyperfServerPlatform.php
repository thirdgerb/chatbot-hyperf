<?php


namespace Commune\Chatbot\Hyperf\Servers;


use Swoole\Runtime;
use Swoole\Coroutine;
use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;
use Hyperf\Server\ServerFactory;
use Commune\Blueprint\Configs\PlatformConfig;
use Commune\Blueprint\Host;
use Commune\Chatbot\Hyperf\Support\HyperfUtils;
use Hyperf\Server\ServerInterface;
use Commune\Blueprint\Kernel\Protocals\AppRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Commune\Blueprint\Platform;
use Commune\Blueprint\Shell;
use Commune\Platform\AbsPlatform;

/**
 * 在 Commune Platform 中启动 Hyperf 配置好的 Server.
 */
abstract class AbsHyperfServerPlatform extends AbsPlatform
{

    /**
     * @var Shell
     */
    protected $shell;

    /*---- cached ----*/

    /**
     * Hyperf container
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ServerInterface
     */
    protected $server;

    public function __construct(
        Host $host,
        Shell $shell,
        PlatformConfig $config,
        LoggerInterface $logger
    )
    {
        $this->shell = $shell;
        parent::__construct($host, $config, $logger);
    }


    abstract protected function initializeHyperf() : void;

    abstract public function getHyperfPlatformOption() : HfPlatformOption;


    public function serve(): void
    {
        // 环境检查.
        $this->checkEnvironment();

        $this->initializeHyperf();

        $console = $this->host->getConsoleLogger();
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        /**
         * @var ServerFactory $serverFactory
         */
        $serverFactory = $this
            ->container
            ->get(ServerFactory::class)
            ->setEventDispatcher($dispatcher)
            ->setLogger($console);

        // 初始化配置.
        $serverConfig = $this->getHyperfPlatformOption()->toServerConfigArray();
        $serverFactory->configure($serverConfig);

        Runtime::enableCoroutine(true, swoole_hook_flags());

        $this->server = $serverFactory->getServer();

        $this->server->start();
    }

    public function shutdown(): void
    {
        if (isset($this->server)) {
            $this->server->getServer()->shutdown();
        }
    }

    protected function checkEnvironment() :  void
    {
        $error = HyperfUtils::checkEnvironment();
        if ($error) {
            $this->host->getConsoleLogger()->critical($error);
            exit(SIGTERM);
        }
    }


    public function getAppId(): string
    {
        return $this->shell->getId();
    }

    public function sleep(float $seconds): void
    {
        Coroutine::sleep($seconds);
    }


    protected function handleRequest(
        Platform\Adapter $adapter,
        AppRequest $request,
        string $interface = null
    ): void
    {
        $response = $this->shell->handleRequest(
            $request,
            $interface
        );
        $adapter->sendResponse($response);
    }



}