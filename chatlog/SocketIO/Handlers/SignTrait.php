<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatbot\Hyperf\Coms\SocketIO\ProtocalException;
use Commune\Chatlog\SocketIO\Protocal\UserLogin;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\DTO\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;

/**
 * @mixin ChatlogEventHandler
 */
trait SignTrait
{

    /**
     * 登录一个用户, 告知客户端用户已经登录.
     *
     * @param UserInfo $user
     * @param string $token
     * @param ChatlogSioRequest $request
     * @param BaseNamespace $controller
     * @param Socket $socket
     * @return array
     */
    public function loginUser(
        UserInfo $user,
        string $token,
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        $login = new UserLogin([
            'id' => $user->id,
            'name' => $user->name,
            'token' => $token
        ]);

        // 发送已登录的消息.
        $response = $request->makeResponse($login);
        // 通知用户登录成功
        $socket->emit($response->event, $response->toEmit());

        // 给用户推荐默认的房间.
        return $this->initializeUser($user, $request, $socket);
    }

    /**
     * 校验用户的注册信息.
     *
     * @param ChatlogSioRequest $request
     * @param Socket $socket
     * @return array|null
     */
    public function validateSign(
        ChatlogSioRequest $request,
        Socket $socket
    ) : ? array
    {
        $data = $request->proto;
        try {
            $sign = new SignInfo($data);
        } catch (\Throwable $e) {
            throw new ProtocalException('登录信息不正确', $e);
        }

        $name = $sign->name;
        $nameLength = mb_strlen($name);
        if ($nameLength > 20) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                "用户名[$name]过长",
                $request,
                $socket
            );
            return [];
        }

        if ($nameLength < 1) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                "用户名为空",
                $request,
                $socket
            );
            return [];
        }

        $password = $sign->password;
        $length = strlen($password);

        if ($length > 0 && $length < 8) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                '密码需要至少8个字符',
                $request,
                $socket
            );
            return [];
        }

        if ($length > 32) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                '密码最多32个字符.',
                $request,
                $socket
            );
            return [];
        }


        $request->with(SignInfo::class, $sign);
        return null;
    }


    /**
     * 初始化用户, 并且给用户推荐房间.
     * 包括立刻加入的, 和主动推荐的.
     *
     * @param UserInfo $user
     * @param ChatlogSioRequest $request
     * @param Socket $socket
     * @return array
     */
    protected function initializeUser(
        UserInfo $user,
        ChatlogSioRequest $request,
        Socket $socket
    ) : array
    {
        // 提供一个系统可以感知的房间地址.
        // 其实本来 hyperf socket.io 也会干这个事情.
        // 不过那个房间要感知挺麻烦的, 还会随着掉线而变动.
        $socket->join($user->id);
        return [];
    }
}