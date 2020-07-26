<?php


namespace Commune\Chatlog\SocketIO\Middleware;


use Commune\Chatbot\Hyperf\Coms\SocketIO\EventPipe;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Hyperf\SocketIOServer\Socket;

class AuthorizePipe implements EventPipe
{
    /**
     * @var Socket
     */
    protected $socket;

    /**
     * AuthorizePipe constructor.
     * @param Socket $socket
     */
    public function __construct(Socket $socket)
    {
        $this->socket = $socket;
    }


    public function handle(SioRequest $request, \Closure $next): array
    {
        $user = $request->getTemp(UserInfo::class);
        if (empty($user)) {
            $error = '当前操作必须登录';
            $errorInfo = new ErrorInfo([
                'errcode' => ErrorInfo::UNAUTHORIZED,
                'errmsg' => $error,
            ]);
            $res = $request->makeResponse($errorInfo);
            $this->socket->emit($res->event, $res->toEmit());
            return [];
        }

        return $next($request);
    }


}