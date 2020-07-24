<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Commune\Chatlog\SocketIO\ChatlogConfig;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\ValidationData;

class JwtFactory
{

    /**
     * @var ChatlogConfig
     */
    protected $config;

    /**
     * JwtFactory constructor.
     * @param ChatlogConfig $config
     */
    public function __construct(ChatlogConfig $config)
    {
        $this->config = $config;
    }

    public function getConfig() : ChatlogConfig
    {
        return $this->config;
    }

    public function getSigner() :Signer
    {
        $abstract = $this->config->jwtSigner;
        return new $abstract;
    }

    /**
     * @return Key|string
     */
    public function getKey()
    {
        return new Key($this->config->jwtSecret);
    }

    public function issueToken(UserInfo $user) : Token
    {
        $signer = $this->getSigner();
        $key = $this->getKey();
        $time = time();

        return (new Builder())
            ->issuedBy($this->config->appName)
            ->permittedFor($user->name)
            ->identifiedBy($user->id, true)
            ->withClaim('lvl', $user->level)
            ->issuedAt($time)
            ->expiresAt($time + $this->config->jwtExpire)
            ->getToken($signer, $key);
    }

    public function fetchUser(Token $token) : ? UserInfo
    {
        $id = $token->getHeader('jti');
        $name = $token->getClaim('aud');
        $lvl = $token->getClaim('lvl');

        if (empty($id) || empty($name) || is_null($lvl)) {
            return null;
        }

        return new UserInfo([
            'id' => $id,
            'name' => $name,
            'level' => (int) $lvl,
        ]);
    }

    /**
     * @param string $jwt
     * @return Token|null
     * @throws \InvalidArgumentException
     */
    public function parse(string $jwt) : ? Token
    {
        $parser = new Parser();
        return $parser->parse($jwt);
    }

    public function verify(Token $token) : ? string
    {
        $signer = $this->getSigner();
        $key = $this->getKey();
        return $token->verify($signer, $key) ? null : 'invalid sign';
    }

    public function getValidateData()
    {
        return new ValidationData();
    }

    public function validateIssuer(ValidationData $data, Token $token) : ? string
    {
        $data->setIssuer($this->config->appName);
        return $token->validate($data) ? null : 'invalid issuer';
    }

    public function validateUser(ValidationData $data, Token $token, UserInfo $userInfo) : ? string
    {
        $data->setAudience($userInfo->name);
        $data->setId($userInfo->id);
        return $token->validate($data) ? null : 'invalid user';
    }

    public function validateTime(ValidationData $data, Token $token, int $now = null) : ? string
    {
        $now = $now ?? time();
        $data->setCurrentTime($now);
        return $token->validate($data) ? null : 'token expired';
    }
}