<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;


/**
 * 事件管道.
 */
interface EventPipe
{
    /**
     * Response 是 Errors. 为空表示请求处理好了.
     *
     * @param SioRequest $request
     * @param \Closure $next
     * @return string[]
     */
    public function handle(SioRequest $request, \Closure $next) : array;
}