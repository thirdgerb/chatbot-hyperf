<?php


namespace Commune\Chatbot\Hyperf\Hacks\Websocket;

use Commune\Blueprint\Host;
use Commune\Contracts\Log\ConsoleLogger;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Dispatcher\HttpDispatcher;
use Hyperf\ExceptionHandler\ExceptionHandlerDispatcher;
use Hyperf\HttpServer\ResponseEmitter;
use Hyperf\HttpServer\Router\Router;
use Hyperf\WebSocketServer\Server;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Swoole\Http\Request as SwooleRequest;
use Swoole\Http\Response as SwooleResponse;
use Swoole\WebSocket\Frame;

/**
 * Hyperf 默认的 Server 有一些设置不方便, 这里 hack 一下.
 */
class HfWebsocketServer extends Server
{

    /**
     * @var Host
     */
    protected $host;

    /**
     * @var ConsoleLogger
     */
    protected $console;

    public function __construct(
        ContainerInterface $container,
        HttpDispatcher $dispatcher,
        ExceptionHandlerDispatcher $exceptionHandlerDispatcher,
        ResponseEmitter $responseEmitter,
        StdoutLoggerInterface $logger,
        Host $host
    )
    {
        $this->host = $host;
        $this->console = $host->getConsoleLogger();

        parent::__construct($container, $dispatcher, $exceptionHandlerDispatcher, $responseEmitter, $logger);

        // 替换掉当前 Server 的 logger.
        // 因为默认会把日志都记录到 Console 里, 与 Commune 策略不一致.
        $this->logger = $this->host->getProcContainer()->make(LoggerInterface::class);
    }
}