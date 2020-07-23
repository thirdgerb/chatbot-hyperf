<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Blueprint\Shell;
use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatbot\Hyperf\Coms\SocketIO\AbsChatlogEventHandler;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Commune\Contracts\Log\ExceptionReporter;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Psr\Log\LoggerInterface;


/**
 * 用户信息登入. 也会给用户进行初始化.
 */
class SignHandler extends AbsChatlogEventHandler implements HasIdGenerator
{
    use IdGeneratorHelper, SignTrait;

    protected $middlewares = [
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
    ];

    /**
     * @var ChatlogUserRepo
     */
    protected $repo;

    /**
     * @var JwtFactory
     */
    protected $jwtFactory;

    public function __construct(
        JwtFactory $factory,
        Shell $shell,
        ChatlogUserRepo $repo,
        LoggerInterface $logger,
        ExceptionReporter $reporter
    )
    {
        $this->jwtFactory = $factory;
        $this->repo = $repo;
        parent::__construct($shell, $logger, $reporter);
    }

    function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        return $this->isTokenSign($request, $controller, $socket)
            ?? $this->validateSign($request, $socket)
            ?? $this->isGuestSign($request, $controller, $socket)
            ?? $this->isUserSign($request, $controller, $socket);
    }

    protected function isTokenSign(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : ? array
    {
        $user = $request->user;
        // 是否用 token 登录
        if (empty($user)) {
            return null;
        }
        return $this->initializeUser($user, $request, $controller, $socket);
    }


    protected function isUserSign(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        $sign = $request->getTemp(SignInfo::class);
        $user = $this->findUser($sign);

        if (empty($user)) {
            return $this->emitErrorInfo(
                ErrorInfo::UNAUTHORIZED,
                $error = '用户信息不存在',
                $request,
                $socket
            );
        }

        return $this->loginUser(
            $user,
            $this->makeToken($user),
            $request,
            $controller,
            $socket
        );
    }

    protected function isGuestSign(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : ? array
    {
        /**
         * @var SignInfo $sign
         */
        $sign = $request->getTemp(SignInfo::class);
        // 有密码就不是访客登录.
        if (!empty($sign->password)) {
            return null;
        }

        $name = $sign->name;
        $uuid = $this->createUuId();
        $user = $this->createGuest($uuid, $name);

        return $this->loginUser(
            $user,
            $this->makeToken($user),
            $request,
            $controller,
            $socket
        );
    }

    protected function makeToken(UserInfo $user) : string
    {
        return (string) $this->jwtFactory->issueToken($user);
    }

    protected function createGuest(
        string $uuid,
        string $name
    ) : UserInfo
    {
        return new UserInfo([
            'id' => $uuid,
            'name' => $name,
            'level' => Supervise::GUEST,
        ]);
    }

    protected function findUser(SignInfo $login) : ? UserInfo
    {
        $user = $this->repo->verifyUser($login->name, $login->password);
        if (!empty($user)) {
            return new UserInfo([
                'id' => $user->user_id,
                'name' => $user->name,
                'level' => $user->level,
            ]);
        }
        return null;
    }


}