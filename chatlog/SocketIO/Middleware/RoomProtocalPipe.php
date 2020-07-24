<?php


namespace Commune\Chatlog\SocketIO\Middleware;


use Commune\Chatbot\Hyperf\Coms\SocketIO\EventPipe;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioRequest;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Hyperf\SocketIOServer\Socket;

class RoomProtocalPipe implements EventPipe
{
    use RoomVerifyTrait;

    /**
     * @var RoomService
     */
    protected $service;

    /**
     * @var Socket
     */
    protected $socket;

    /**
     * RoomProtocalPipe constructor.
     * @param RoomService $service
     * @param Socket $socket
     */
    public function __construct(RoomService $service, Socket $socket)
    {
        $this->service = $service;
        $this->socket = $socket;
    }

    /**
     * @param ChatlogSioRequest $request
     * @param \Closure $next
     * @return array
     */
    public function handle(SioRequest $request, \Closure $next): array
    {
        $proto = $request->proto;
        $scene = $proto['scene'] ?? '';
        $session = $proto['session'] ?? '';

        return $this->verifyRoom(
                $scene,
                $session,
                $this->service,
                $request,
                $this->socket
            )
            ?? $next($request);
    }


}