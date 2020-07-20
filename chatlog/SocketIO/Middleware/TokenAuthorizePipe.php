<?php


namespace Commune\Chatlog\SocketIO\Middleware;


use Commune\Chatlog\SocketIO\Blueprint\EventPipe;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\SioResponse;
use Hyperf\SocketIOServer\Socket;

class TokenAuthorizePipe implements EventPipe
{
    /**
     * @var Socket
     */
    protected $socket;

    public function handle(SioRequest $request, \Closure $next): array
    {
        if (empty($request->token)) {
            $request->makeResponse(
                null,
                SioResponse::UNAUTHORIZED,
                'miss token'
            );
        }
        return $next($request);
    }


}