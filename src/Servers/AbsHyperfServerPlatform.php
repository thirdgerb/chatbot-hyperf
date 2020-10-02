<?php


namespace Commune\Chatbot\Hyperf\Servers;


use Hyperf\Contract\ConfigInterface;
use Hyperf\Utils\ApplicationContext;
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
        $this->container = ApplicationContext::getContainer();
        parent::__construct($host, $config, $logger);
    }


    /**
     * 初始化 Hyperf 服务端的逻辑. 比如注册控制器等.
     */
    abstract protected function initializeHyperf() : void;

    /**
     * 获取 Hyperf 平台的配置.
     * Hyperf 作为微服务框架, 一个项目相当于 Commune 中的一个平台.
     * 由于 Commune 项目要做同构, 所以要有能力启动多个项目.
     *
     * @return HfPlatformOption
     */
    abstract public function getHyperfPlatformOption() : HfPlatformOption;

    /**
     * 由于 Hyperf 自身是微服务框架
     * 而 Commune Studio 是全栈式的, 要通过 hyperf 开启若干个端.
     * 因此许多 Hyperf 的单点配置策略在这里都要改变为可以任意配置的.
     * 因此有这个环节.
     */
    protected function hackHyperf() : void
    {
        $option = $this->getHyperfPlatformOption();
        /**
         * @var ConfigInterface $config
         */
        $config = ApplicationContext::getContainer()->get(ConfigInterface::class);

        foreach ($option->servers as $server) {
            $this->hackMiddleware($config, $server);
            $this->hackExceptionHandlers($config, $server);
        }
    }

    /**
     * 使用 Server 的 exceptionHandler 替换 Hyperf 的默认配置
     * @param ConfigInterface $config
     * @param HfServerOption $option
     */
    protected function hackExceptionHandlers(ConfigInterface $config, HfServerOption $option) : void
    {
        $hacks = $option->exceptionHandlers;
        if (is_null($hacks)) {
            return;
        }

        $serverName = $option->name;
        // 按 hyperf 的规范设置异常处理.
        $key = 'exceptions.handler.' . $serverName;
        $config->set($key, $hacks);
    }

    /**
     * 使用 Server 的中间件配置替换掉 hyperf 的相关配置.
     * @param ConfigInterface $config
     * @param HfServerOption $option
     */
    protected function hackMiddleware(ConfigInterface $config, HfServerOption $option)  : void
    {
        $hacks = $option->middelware;
        if (is_null($hacks)) {
            return;
        }

        // 设置 middleware 的中间件.
        $serverName = $option->name;
        $key = 'middlewares.' . $serverName;
        $config->set($key, $hacks);
    }

    /**
     * 启动 Hyperf 的 Server.
     */
    public function serve(): void
    {
        // 环境检查.
        $this->checkEnvironment();

        // 替换 hyperf 的全局配置
        $this->hackHyperf();

        // 对 hyperf 进一步初始化, 比如可以注册路由
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
        $serverConfig = $this
            ->getHyperfPlatformOption()
            ->toServerConfigArray();

        $serverFactory->configure($serverConfig);

        Runtime::enableCoroutine(true, swoole_hook_flags());

        $this->server = $serverFactory->getServer();

        $serverFactory->start();
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