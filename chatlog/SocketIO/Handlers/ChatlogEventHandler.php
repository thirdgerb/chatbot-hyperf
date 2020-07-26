<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Blueprint\CommuneEnv;
use Commune\Chatbot\Hyperf\Coms\SocketIO\AbsEventHandler;
use Commune\Chatbot\Hyperf\Coms\SocketIO\ProtocalException;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioRequest;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioResponse;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\ChatlogConfig;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\QuitChat;
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
abstract class ChatlogEventHandler extends AbsEventHandler implements HasIdGenerator
{
    use IdGeneratorHelper;

    /**
     * @var ChatlogConfig|null
     */
    protected $config;

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

    public function isDebug(): bool
    {
        return $this->getConfig()->debug;
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

        try {

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

        } catch (ProtocalException $e) {
            static::emitErrorInfo(
                $e->getCode(),
                $e->getMessage(),
                $request,
                $socket
            );
        } catch (\Throwable $e) {
            $this->expReporter->report($e);
            $socket->emit('error', $e->getMessage());
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
            $socket->emit('error', $error);
            return null;
        }

        try {
            $data['event'] = $event;
            $request = new ChatlogSioRequest($data);

            if (CommuneEnv::isDebug()) {
                $this
                ->logger
                ->debug("incoming event $event:" . $request->toJson());
            }
            return $request;

        } catch (\Throwable $e) {
            $error = get_class($e) . ': ' . $e->getMessage();
            $socket->emit('error', $error);
            // 异常请求只记录一下
            $this->logger->warning($error);
            return null;
        }
    }


    /*----- helpers -----*/


    /**
     * 禁止行为.
     * @param string $error
     * @param ChatlogSioRequest $request
     * @param Socket $socket
     * @return array
     */
    public static function forbidden(
        string $error,
        ChatlogSioRequest $request,
        Socket $socket
    ) : array
    {
        return static::emitErrorInfo(
            ErrorInfo::FORBIDDEN,
            $error,
            $request,
            $socket
        );
    }

    public static function makeUserQuitChat(
        string $session,
        string $message,
        ChatlogSioRequest $request,
        Socket $socket
    ) : array
    {
        $proto = new QuitChat(['session' => $session, 'message'=> $message]);
        $request->makeResponse($proto)->emit($socket);
        return [];
    }

    /**
     * 通知一个异常信息.
     *
     * @param int $code
     * @param string $message
     * @param ChatlogSioRequest $request
     * @param Socket $socket
     * @return array
     */
    public static function emitErrorInfo(
        int $code,
        string $message,
        SioRequest $request,
        Socket $socket
    ) : array
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

    /*----- 工具类 -----*/

    public function getConfig() : ChatlogConfig
    {
        return $this->config
            ?? $this->config = $this->container->make(ChatlogConfig::class);
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


    public function getRoomService() : RoomService
    {
        return $this
            ->container
            ->make(RoomService::class);
    }

    /*----- 内部方法. -----*/

    public function deliverToChatbot(
        MessageBatch $batch,
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : void
    {


    }


    public function informSupervisor(
        string $message,
        SioRequest $request,
        BaseNamespace $emitter
    ) : void
    {
        $session = $this->getRoomService()->getSupervisorSession();
        $text = TextMessage::instance($message);
        $response = $this->makeSystemMessage(
            $text,
            $session,
            $request
        );
        $emitter->to($session)->emit($response->event, $response->toEmit());
    }


    public function makeSystemMessage(
        ChatlogMessage $message,
        string $session,
        SioRequest $request
    ) : SioResponse
    {
        $batch = new MessageBatch([
            'mode' => MessageBatch::MODE_SYSTEM,
            'session' => $session,
            'batchId' => $this->createUuId(),
            'creatorId' => $this->config->getAppId(),
            'creatorName' => $this->config->appName,
            'messages' => [
                $message
            ],
            'createdAt' => time(),
        ]);
        return $request->makeResponse($batch);
    }
}