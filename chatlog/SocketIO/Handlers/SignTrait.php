<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Protocal\LoginInfo;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;

trait SignTrait
{

    public function loginUser(
        UserInfo $user,
        string $token,
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        $login = new LoginInfo([
            'id' => $user->id,
            'name' => $user->name,
            'token' => $token
        ]);

        // 发送已登录的消息.
        $response = $request->makeResponse($login);
        $socket->emit($response->event, $response->toEmit());
        return $this->initializeUser($user, $request, $controller, $socket);
    }

    public function validateSign(
        ChatlogSioRequest $request,
        Socket $socket
    ) : ? array
    {
        $data = $request->proto;
        $sign = new SignInfo($data);

        $nameLength = mb_strlen($sign->name);
        if ($nameLength > 12) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                '用户名过长',
                $request,
                $socket
            );
            return [];
        }

        if ($nameLength < 2) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                '用户名太短',
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

        if ($length > 16) {
            $this->emitErrorInfo(
                ErrorInfo::UNPROCESSABLE_ENTITY,
                '密码最多16个字符.',
                $request,
                $socket
            );
            return [];
        }


        $request->with(SignInfo::class, $sign);
        return null;
    }


    protected function initializeUser(
        UserInfo $user,
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ) : array
    {
        // 提供一个系统可以感知的房间地址.
        // 其实本来 socket 也会干这个事情.
        $socket->join($user->id);
//
//        // 按权限加入各种房间.
//        if ($user->level === Supervise::SUPERVISOR) {
//            //todo
//        }
        return [];
    }

}