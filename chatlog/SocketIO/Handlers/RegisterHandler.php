<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Blueprint\Shell;
use Commune\Chatbot\Hyperf\Coms\SocketIO\EventHandler;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Psr\Log\LoggerInterface;

class RegisterHandler extends EventHandler implements HasIdGenerator
{
    use SignTrait, IdGeneratorHelper;

    /**
     * @var ChatlogUserRepo
     */
    protected $repo;

    /**
     * @var JwtFactory
     */
    protected $jwtFactory;

    protected $middlewares = [
        RequestGuardPipe::class,
    ];

    public function __construct(
        Shell $shell,
        ChatlogUserRepo $repo,
        JwtFactory $factory,
        LoggerInterface $logger,
        ExceptionReporter $reporter
    )
    {
        $this->repo = $repo;
        $this->jwtFactory = $factory;
        parent::__construct($shell, $logger, $reporter);
    }

    function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        return $this->validateSign($request, $socket)
            ?? $this->registerSign($request, $controller, $socket);
    }

    protected function registerSign(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        /**
         * @var SignInfo $sign
         */
        $sign = $request->getTemp(SignInfo::class);
        $name = $sign->name;
        if ($this->repo->userNameExists($name)) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                "名字 [$name] 已被占用",
                $request,
                $socket
            );
            return [];
        }

        $success = $this->repo->register(
            $userId = $this->createUuId(),
            $username = $sign->name,
            $sign->password,
            $level = Supervise::USER
        );

        if (!$success) {
            return $this->emitErrorInfo(
                ErrorInfo::HOST_REQUEST_FAIL,
                "请求失败, 请重试",
                $request,
                $socket
            );
        }

        $userInfo = new UserInfo([
            'id' => $userId,
            'name' => $username,
            'level' => $level
        ]);

        $token = $this->jwtFactory->issueToken($userInfo);

        return $this->loginUser(
            $userInfo,
            $token,
            $request,
            $controller,
            $socket
        );
    }


}