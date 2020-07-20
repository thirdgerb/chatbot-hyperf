<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Blueprint\Shell;
use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatlog\SocketIO\Blueprint\EventHandler;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
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
        $data = $request->proto;
        $login = new SignInfo($data);

        // 访客登录.
        if (empty($login->password)) {
            $uuid = $request->trace;
            $user = $this->createGuest($uuid, $login->name);

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

        // 正式登录..

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
    }



}