<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Blueprint\Shell;
use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatlog\SocketIO\Blueprint\EventHandler;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\Login;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Commune\Contracts\Log\ExceptionReporter;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

use Commune\Chatlog\SocketIO\Middleware\TokenAuthorizePipe;
use Psr\Log\LoggerInterface;


class SignHandler extends EventHandler
{
    protected $middlewares = [
        TokenAuthorizePipe::class,
    ];


    /**
     * @var JwtFactory
     */
    protected $jwtFactory;

    public function __construct(
        JwtFactory $factory,
        Shell $shell,
        LoggerInterface $logger,
        ExceptionReporter $reporter
    )
    {
        $this->jwtFactory = $factory;
        parent::__construct($shell, $logger, $reporter);
    }

    function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        return $this->isTokenSign($request, $controller, $socket)
            ?? $this->isGuestSign($request, $controller, $socket)
            ?? $this->isUserSign($request, $controller, $socket);
    }

    protected function isTokenSign(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : ? array
    {
        $user = $request->user;
        // 是否用 token 登录
        if (empty($user)) {
            return null;
        }

        $this->initializeUser($user, $request, $controller, $socket);
        return [];
    }

    protected function isUserSign(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        $data = $request->proto;
        $sign = new SignInfo($data);

        $user = $this->findUser($sign);
        if (empty($user)) {
            $error = new ErrorInfo([
                'errcode' => ErrorInfo::UNAUTHORIZED,
                'errmsg' => $message = '登录信息有误',
            ]);

            $request->makeResponse($error)->emit($socket);
            return [static::class => $error];
        }

        $this->initializeUser($user, $request, $controller, $socket);
        return [];
    }

    protected function isGuestSign(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : ? array
    {

        $data = $request->proto;
        $sign = new SignInfo($data);

        // 有密码就不是访客登录.
        if (!empty($sign->password)) {
            return null;
        }

        $uuid = $request->trace;
        $user = $this->createGuest($uuid, $sign->name);
        $this->initializeUser($user, $request, $controller, $socket);

        $login = new Login([
            'id' => $user->id,
            'name' => $user->name,
            'token' => $this->makeToken($user)
        ]);

        // 发送已登录的消息.
        $response = $request->makeResponse($login);
        $socket->emit($response->event, $response->toEmit());
        return [];
    }




    protected function makeToken(UserInfo $user) : string
    {
        return (string) $this->jwtFactory->issueToken($user);
    }

    protected function createGuest(string $uuid, string $name) : UserInfo
    {
        return new UserInfo([
            'id' => $uuid,
            'name' => $name,
            'level' => Supervise::GUEST,
        ]);
    }

    protected function findUser(SignInfo $login) : ? UserInfo
    {
        // todo
        return null;
    }


    protected function initializeUser(
        UserInfo $user,
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : void
    {
        $socket->join($user->id);

        // 按权限加入各种房间.
        if ($user->level === Supervise::SUPERVISOR) {
            //todo
        }
    }

}