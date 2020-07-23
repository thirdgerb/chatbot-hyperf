<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatbot\Hyperf\Coms\SocketIO\EventHandler;
use Commune\Chatlog\SocketIO\Middleware\AuthorizePipe;
use Commune\Chatlog\SocketIO\Middleware\RequestGuardPipe;
use Commune\Chatlog\SocketIO\Middleware\TokenAnalysePipe;
use Commune\Chatlog\SocketIO\Protocal\Input;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class InputHandler extends EventHandler
{
    protected $middlewares =[
        RequestGuardPipe::class,
        TokenAnalysePipe::class,
        AuthorizePipe::class,
    ];

    function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $user = $request->user;
        $input = Input::create($request->proto);
        $broadcasting = MessageBatch::fromInput($input, $user);

        $response = $request->makeResponse($broadcasting);
        $socket->to($input->session)->emit($response->event, $response->toEmit());
        $this->console->info('to '. $input->session, $response->toArray());

        if ($input->bot) {
            // deliver to chatbot
        }

        return [];
    }


}