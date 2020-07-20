<?php


namespace Commune\Chatlog\SocketIO\Handlers;


use Commune\Chatlog\ChatlogConfig;
use Commune\Chatlog\SocketIO\Blueprint\EventHandler;
use Commune\Chatlog\SocketIO\Protocal\SignInfo;
use Commune\Chatlog\SocketIO\Protocal\Login;
use Commune\Chatlog\SocketIO\Protocal\SioRequest;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Rsa\Sha256;

class SignHandler extends EventHandler
{
    function handle(
        SioRequest $request,
        BaseNamespace $controller,
        Socket $socket
    ): array
    {
        $data = $request->proto;
        $login = new SignInfo($data);

        // 访客登录.
        if (empty($login->password)) {
            $uuid = $request->trace;
            $user = $this->createGuest($uuid, $login->name);

            $login = new Login([
                'id' => $user->id,
                'name' => $user->name,
                'token' => $this->makeToken($user)
            ]);

            // 发送消息.
            $response = $request->makeResponse($login);
            $socket->emit($response->event, $response->toEmit());

            return [];
        }

        // 正式登录..

        return [];
    }

    protected function makeToken(UserInfo $user) : string
    {
        /**
         * @var ChatlogConfig $config
         */
        $config = $this->container->make(ChatlogConfig::class);

        $signer = new Sha256();
        $time = time();
        $token = (new Builder())
            ->issuedBy($this->shell->getId())
            ->permittedFor($user->name)
            ->identifiedBy($user->id, true)
            ->issuedAt($time)
            ->expiresAt($time + 86400 * 2) // Configures the expiration time of the token (exp claim)
            ->getToken($signer, $config->jwtSecret); // Retrieves the generated token

        return (string) $token;
    }

    protected function createGuest(string $uuid, string $name) : UserInfo
    {
        return new UserInfo([
            'id' => $uuid,
            'name' => $name,
            'authorized' => false,
            'supervise' => false,
        ]);
    }

    protected function findUser(SignInfo $login) : ? UserInfo
    {
    }



}