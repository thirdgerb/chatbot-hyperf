<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\DTO\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class RegisterHandler extends ChatlogEventHandler implements HasIdGenerator
{
    use SignTrait, IdGeneratorHelper;


    protected $middlewares = [
        RequestGuardPipe::class,
    ];

    function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        return $this->validateSign($request, $socket)
            ?? $this->registerSign($request, $controller, $socket);
    }

    protected function registerSign(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        /**
         * @var SignInfo $sign
         */
        $sign = $request->getTemp(SignInfo::class);
        $name = $sign->name;
        $repo = $this->getUserRepo();
        if ($repo->userNameExists($name)) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                "名字 [$name] 已被占用",
                $request,
                $socket
            );
            return [];
        }

        $success = $repo->register(
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

        $this->informSupervisor(
            "用户注册: $username",
            $request,
            $controller
        );

        $token = $this->getJwtFactory()->issueToken($userInfo);
        return $this->loginUser(
            $userInfo,
            $token,
            $request,
            $controller,
            $socket
        );
    }


}