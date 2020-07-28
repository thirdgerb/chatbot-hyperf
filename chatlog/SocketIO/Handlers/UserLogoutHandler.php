<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class UserLogoutHandler extends ChatlogEventHandler
{
    protected $middlewares =[
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
        AuthorizePipe::class,
    ];
    function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $socket->leaveAll();
        return [];
    }


}