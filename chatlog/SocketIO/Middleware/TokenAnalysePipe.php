<?php


namespace Commune\Chatlog\SocketIO\Middleware;

use Commune\Chatbot\Hyperf\Coms\SocketIO\EventPipe;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioRequest;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Hyperf\SocketIOServer\Socket;

class TokenAnalysePipe implements EventPipe
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
                return $this->unAuthorize(
                    $error,
                    $request,
                    $this->socket
                );
            }

            // 设置用户的信息
            $user = $this->jwtFactory->fetchUser($token);
            if (empty($user)) {
                return $this->unAuthorize(
                    '用户信息不存在',
                    $request,
                    $this->socket
                );
            }

            $userId = $user->id;
            $username = $user->name;
            if (empty($userId) || empty($username)) {
                return $this->unAuthorize(
                    '登录信息不正确',
                    $request,
                    $this->socket
                );
            }

            $request->with(UserInfo::class, $user);
            return null;

        } catch (\Throwable $e) {

            return [get_class($e), $e->getMessage()];
        }

    }

    protected function unAuthorize(
        string $error,
        SioRequest $request,
        Socket $socket
    )
    {
        $errorInfo = new ErrorInfo([
            'errcode' => ErrorInfo::UNAUTHORIZED,
            'errmsg' => $error,
        ]);
        $this->socket->leaveAll();
        $request->makeResponse($errorInfo)->emit($this->socket);
        return [];
    }
}