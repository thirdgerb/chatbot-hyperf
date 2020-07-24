<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;

use Hyperf\SocketIOServer\Socket;
use Commune\Blueprint\Shell;
use Hyperf\SocketIOServer\BaseNamespace;
use Psr\Log\LoggerInterface;
use Commune\Blueprint\Framework\ProcContainer;
use Commune\Contracts\Log\ConsoleLogger;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;

abstract class AbsEventHandler
{

    /**
     * 中间件
     * @var string[]
     */
    protected $middlewares = [];


    /*---- 依赖注入 ----*/

    /**
     * @var Shell
     */
    protected $shell;

    /**
     * @var ProcContainer
     */
    protected $container;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ExceptionReporter
     */
    protected $expReporter;

    /**
     * @var ConsoleLogger
     */
    protected $console;

    /**
     * @var HfSocketIOPlatform
     */
    protected $platform;

    /**
     * AbsEventHandler constructor.
     * @param Shell $shell
     * @param HfSocketIOPlatform $platform
     * @param LoggerInterface $logger
     * @param ExceptionReporter $reporter
     */
    public function __construct(
        Shell $shell,
        HfSocketIOPlatform $platform,
        LoggerInterface $logger,
        ExceptionReporter $reporter
    )
    {
        $this->shell = $shell;
        $this->platform = $platform;
        $this->console = $shell->getConsoleLogger();
        $this->container = $shell->getProcContainer();
        $this->logger = $logger;
        $this->expReporter = $reporter;
    }

    abstract public function isDebug() : bool;

    public function __invoke(
        string $event,
        BaseNamespace $controller,
        Socket $socket,
        $data
    ) : void
    {
        if ($this->isDebug()) {
            $this->logger->debug(
                "incoming event: $event, data: "
                . json_encode(
                    $data,
                    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                )
            );
        }

        $request = $this->fetchRequest($event, $socket, $data);
        // 如果请求为空, 则已经记录了错误信息.
        if (empty($request)) {
            return;
        }

        $start = microtime(true);

        try {

            $this->handleRequest($request, $controller, $socket);

        } catch (ProtocalException $e) {

            $this->logger->warning($e);
            static::emitErrorInfo(
                $e->getCode(),
                $e->getMessage(),
                $request,
                $socket
            );


        } catch (\Throwable $e) {

            $this->expReporter->report($e);
            $socket->emit('error', get_class($e) . ':' . $e->getMessage());
        }

        $end = microtime(true);
        $gap = round(($end-$start) * 1000000, 0);
        $name = $this->shell->getId();

        // 记录日志.
        $this->logger->debug(
            "finish shell $name event request in $gap us",
            ['trace' => $request->trace]
        );
    }

    /**
     * @param string $event
     * @param Socket $socket
     * @param $data
     * @return SioRequest
     */
    abstract protected function fetchRequest(
        string $event,
        Socket $socket,
        $data
    ) : ? SioRequest;


    /**
     * @param SioRequest $request
     * @param BaseNamespace $controller
     * @param Socket $socket
     */
    abstract protected function handleRequest(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : void;

    abstract public static function emitErrorInfo(
        int $code,
        string $message,
        SioRequest $request,
        Socket $socket
    ) : array;
}