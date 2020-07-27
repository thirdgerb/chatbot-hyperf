<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\SocketIO\DTO\QueryMessagesInfo;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\RoomProtocalPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\MessagesMerge;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class QueryMessagesHandler extends ChatlogEventHandler
{
    protected $middlewares =[
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
        AuthorizePipe::class,
        RoomProtocalPipe::class,
    ];

    function handle(
        ChatlogSioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $query = new QueryMessagesInfo($request->proto);

        $repo = $this->getMessageRepo();
        $messages = $repo->fetchMessagesByBatchId(
            $query->session,
            $query->limit,
            $query->vernier,
            $query->forward
        );

        $protocal = new MessagesMerge([
            'session' => $query->session,
            'limit' => $query->limit,
            'batches' => $messages,
            'forward' => $query->forward,
        ]);

        $request->makeResponse($protocal)->emit($socket);
        return [];
    }


}