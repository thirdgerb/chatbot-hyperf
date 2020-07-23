<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;


use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Framework\IReqContainer;
use Commune\Support\Pipeline\OnionPipeline;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;


/**
 * Socket IO 的事件处理器.
 *
 * 这个不在 Hyperf 中启动, 而是在 Host 里启动.
 * 因为两个框架处理依赖注入的思路不一样, Hyperf 用的是管理协程上下文的单例
 * 而 Commune 是请求隔离的双容器.
 * 考虑 Commune 内部服务的互通性, 将业务相关的依赖注入转移到 Commune 自己的容器里.
 *
 */
abstract class AbsChatlogEventHandler extends AbsEventHandler implements HasIdGenerator
{
    use IdGeneratorHelper;

    /**
     * @param ChatlogSioRequest $request
     * @param BaseNamespace $controller
     * @param Socket $socket
     * @return array
     */
    abstract function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array;

    protected function errorResponse(
        \Throwable $e,
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): void
    {
        $response = $request->makeResponse(new ErrorInfo([
            'errcode' => ErrorInfo::HOST_REQUEST_FAIL,
            'errmsg' => get_class($e),
        ]));
        $response->emit($socket);
    }


    /**
     * @param ChatlogSioRequest $request
     * @param BaseNamespace $controller
     * @param Socket $socket
     */
    protected function handleRequest(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : void
    {
        $trace = $request->trace;
        $container = new IReqContainer($this->container, $trace);

        $container->share(BaseNamespace::class, $controller);
        $container->share(Socket::class, $socket);
        $container->share(SioRequest::class, $request);
        $container->share(ChatlogSioRequest::class, $request);

        // 是否要用中间件.
        if (!empty($this->middlewares)) {
            $pipes = new OnionPipeline($container, $this->middlewares);
            $errors = $pipes->send($request, function(ChatlogSioRequest $request) use ($controller, $socket){
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

    /**
     * @param string $event
     * @param Socket $socket
     * @param $data
     * @return ChatlogSioRequest|null
     */
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
            return new ChatlogSioRequest($data);

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
        ChatlogSioRequest $request,
        Socket $socket) : array
    {
        $message = empty($message)
            ? ErrorInfo::DEFAULT_ERROR_MESSAGES[$code]
            : $message;

        $error = new ErrorInfo([
            'errcode' => $code,
            'errmsg' => $message,
        ]);

        $request->makeResponse($error)->emit($socket);
        return [];
    }


    public function receiveInputMessage(
        MessageBatch $batch,
        SioRequest $request,
        Socket $socket
    ) : void
    {

        $response = $request->makeResponse($batch);
        $session = $batch->session;

        $socket
            ->to($session)
            ->emit($response->event, $response->toEmit());
    }

    public function getMessageRepo() : ChatlogMessageRepo
    {
        return $this
            ->container
            ->make(ChatlogMessageRepo::class);
    }

    public function getUserRepo() : ChatlogUserRepo
    {
        return $this
            ->container
            ->make(ChatlogUserRepo::class);
    }

    public function getJwtFactory() : JwtFactory
    {
        return $this
            ->container
            ->make(JwtFactory::class);
    }
}