<?php


namespace Commune\Chatlog\Test\SocketIO;


use Commune\Chatlog\ChatlogSocketIOServiceProvider;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use PHPUnit\Framework\TestCase;

class JwtFactoryTest extends TestCase
{

    public function testToken()
    {
        $factory = new JwtFactory(new ChatlogSocketIOServiceProvider([]));
        $user = new UserInfo(['id'=> 'test', 'name' => 'test']);
        $token = $factory->issueToken($user);

        $this->assertEquals($token->getClaim('aud'), $user->name);
        $this->assertEquals($token->getHeader('jti'), $user->id);

        $deUser = $factory->fetchUser($token);

        $this->assertEquals($user->toArray(), $deUser->toArray());
    }

}