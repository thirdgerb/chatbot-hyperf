<?php


namespace Commune\Chatlog\SocketIO\Middleware;

use Commune\Chatlog\SocketIO\Blueprint\EventPipe;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Hyperf\SocketIOServer\Socket;

class TokenAuthorizePipe implements EventPipe
{
    /**
     * @var JwtFactory
     */
    protected $jwtFactory;
    /**
     * @var Socket
     */
    protected $socket;

    /**
     * TokenAuthorizePipe constructor.
     * @param JwtFactory $jwtFactory
     * @param Socket $socket
     */
    public function __construct(JwtFactory $jwtFactory, Socket $socket)
    {
        $this->jwtFactory = $jwtFactory;
        $this->socket = $socket;
    }


    public function handle(SioRequest $request, \Closure $next): array
    {
        return $this->validateToken($request) ?? $next($request);
    }

    protected function validateToken(SioRequest $request) : ? array
    {
        $tokenStr = $request->token;

        // 跳过.
        if (empty($tokenStr)) {
            return null;
        }

        try {
            $token = $this->jwtFactory->parse($tokenStr);
            $validator = $this->jwtFactory->getValidateData();

            // 参数校验.
            $error = $this->jwtFactory->verify($token)
                ?? $this->jwtFactory->validateIssuer($validator, $token)
                ?? $this->jwtFactory->validateTime($validator, $token);

            if ($error) {
                $errorInfo = new ErrorInfo([
                    'errcode' => ErrorInfo::UNAUTHORIZED,
                    'errmsg' => $error
                ]);
                $res = $request->makeResponse($errorInfo);
                $this->socket->emit($res->event, $res->toEmit());

                return [static::class => $error];
            }

            // 设置用户的信息
            $user = $this->jwtFactory->fetchUser($token);
            if (empty($user)) {
                $errorInfo = new ErrorInfo([
                    'errcode' => ErrorInfo::UNAUTHORIZED,
                    'errmsg' => $error = 'user info not exists',
                ]);

                $request->makeResponse($errorInfo)->emit($this->socket);
                return [static::class => $error];
            }

            $request->withUser($user);
            return null;

        } catch (\Throwable $e) {

            return [get_class($e), $e->getMessage()];
        }

    }

}