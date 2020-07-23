<?php


namespace Commune\Chatlog\SocketIO\Middleware;


use Commune\Chatbot\Hyperf\Coms\SocketIO\EventPipe;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;

/**
 * 守卫, 负责限流, 黑名单, 控制数据包大小等等.
 */
class RequestGuardPipe implements EventPipe
{
    public function handle(SioRequest $request, \Closure $next): array
    {
        return $next($request);
    }


}