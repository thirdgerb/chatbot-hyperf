<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;

use Commune\Blueprint\Shell;
use Commune\Blueprint\Framework\ProcContainer;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\SioResponse;
use Commune\Contracts\Log\ConsoleLogger;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Framework\IReqContainer;
use Commune\Support\Pipeline\OnionPipeline;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Psr\Log\LoggerInterface;


/**
 * Socket IO 的事件处理器.
 *
 * 这个不在 Hyperf 中启动, 而是在 Host 里启动.
 * 因为两个框架处理依赖注入的思路不一样, Hyperf 用的是管理协程上下文的单例
 * 而 Commune 是请求隔离的双容器.
 * 考虑 Commune 内部服务的互通性, 将业务相关的依赖注入转移到 Commune 自己的容器里.
 *
 */
abstract class EventHandler implements HasIdGenerator
{
    use IdGeneratorHelper;

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
     * EventHandler constructor.
     * @param Shell $shell
     * @param LoggerInterface $logger
     * @param ExceptionReporter $reporter
     */
    public function __construct(
        Shell $shell,
        LoggerInterface $logger,
        ExceptionReporter $reporter
    )
    {
        $this->shell = $shell;
        $this->console = $shell->getConsoleLogger();
        $this->container = $shell->getProcContainer();
        $this->logger = $logger;
        $this->expReporter = $reporter;
    }


    /**
     * @param SioRequest $request
     * @param BaseNamespace $controller
     * @param Socket $socket
     * @return mixed
     */
    abstract function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array;


    public function __invoke(
        string $event,
        BaseNamespace $controller,
        Socket $socket,
        $data
    ) : void
    {
        //todo 删掉debug.
        $this->console->debug("incoming event: $event, data: " . var_export($data, true));

        $request = $this->fetchRequest($event, $socket, $data);
        if (empty($request)) {
            return;
        }

        $start = microtime(true);

        try {

            $this->handleRequest($request, $controller, $socket);
        } catch (\Throwable $e) {
            $this->expReporter->report($e);
            $response = $request->makeResponse(new ErrorInfo([
                'errcode' => ErrorInfo::HOST_REQUEST_FAIL,
                'errmsg' => get_class($e) . ':' . $e->getMessage(),
            ]));
            $response->emit($socket);
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

    protected function handleRequest(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    )
    {
        $trace = $request->trace;
        $container = new IReqContainer($this->container, $trace);

        $container->share(BaseNamespace::class, $controller);
        $container->share(Socket::class, $socket);
        $container->share(SioRequest::class, $request);

        // 是否要用中间件.
        if (!empty($this->middlewares)) {
            $pipes = new OnionPipeline($container, $this->middlewares);
            $errors = $pipes->send($request, function(SioRequest $request) use ($controller, $socket){
                return $this->handle($request, $controller, $socket);
            });

        } else {
            $errors = $this->handle($request, $controller, $socket);
        }

        // 记录公共的错误信息日志. 这些是逻辑上的问题, 不是给用户的提示.
        if (!empty($errors)) {
            foreach ($errors as $key => $error) {
                $this->logger->error(__METHOD__ . " failed. $key: $error");
            }
        }
    }

    protected function fetchRequest(
        string $event,
        Socket $socket,
        $data
    ) : ? SioRequest
    {
        if (!is_array($data)) {
            $error = 'invalid request data: ' . var_export($data, true);
            $socket->emit('', $error);
            return null;
        }

        try {
            $data['event'] = $event;
            return new SioRequest($data);

        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage();
            $socket->emit('error', $error);
            // 异常请求只记录一下
            $this->logger->warning($error);
            return null;
        }
    }


    /*----- helpers -----*/

    public function emitErrorInfo(
        int $code,
        string $message,
        SioRequest $request,
        Socket $socket) : array
    {
        $message = empty($message)
            ? ErrorInfo::DEFAULT_ERROR_MESSAGES[$code]
            : '';

        $error = new ErrorInfo([
            'errcode' => $code,
            'errmsg' => $message,
        ]);

        $request->makeResponse($error)->emit($socket);
        return [];
    }
}