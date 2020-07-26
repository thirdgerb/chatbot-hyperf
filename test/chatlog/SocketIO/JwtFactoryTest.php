<?php


namespace Commune\Chatlog\Test\SocketIO;


use Commune\Chatlog\ChatlogSocketIOServiceProvider;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;
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

    public function testSign()
    {
        $signer = new Sha256();
        $key = new Key('1234567');
        $hash = bin2hex($signer->createHash('password', $key));
        $this->assertEquals('23e5c47d6e486da392dea360a32ee8e9402c8d5ecfa480c67f8e01f07dc3beb1', $hash);
    }

}