<?php


namespace Commune\Chatlog;


use Commune\Blueprint\CommuneEnv;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\SocketIO\ChatlogConfig;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Handlers;
use Commune\Container\ContainerContract;
use Commune\Contracts\ServiceProvider;
use Lcobucci\JWT\Signer\Hmac\Sha256;

/**
 *
 * @property-read string $appName
 * @property-read string $jwtSigner
 * @property-read string $jwtSecret
 * @property-read int $jwtExpire   seconds
 * @property-read string[] $protocals
 *
 * @property-read string $dbConnection
 * @property-read string $userHashSigner
 * @property-read string $userHashSalt
 */
class ChatlogSocketIOServiceProvider extends ServiceProvider
    implements ChatlogConfig
{
    public static function stub(): array
    {
        return [
            'appName' => 'chatlog',
            'debug' => CommuneEnv::isDebug(),
            'protocals' => [
                'SIGN' => Handlers\SignHandler::class,
                'REGISTER' => Handlers\RegisterHandler::class,
                'JOIN' => Handlers\JoinHandler::class,
                'LEAVE' => Handlers\LeaveHandler::class,
                'INPUT' => Handlers\InputHandler::class,
                'MANUAL' => Handlers\ManualHandler::class,
            ],
            'jwtSigner' => Sha256::class,
            'jwtSecret' => env('CHATLOG_JWT_SECRET', 'helple~~ss'),
            'jwtExpire' => 86400 * 7,

            'dbConnection' => 'default',
            'userHashSigner' => Sha256::class,
            'userHashSalt' => env('CHATLOG_USER_SALT', 'power overwhelming'),
        ];
    }

    public function getAppId(): string
    {
        $salt = $this->jwtSecret;
        $name = $this->appName;
        return sha1("appName:$name:salt:$salt");
    }


    public static function relations(): array
    {
        return [];
    }

    public function getDefaultScope(): string
    {
        return self::SCOPE_PROC;
    }

    public function boot(ContainerContract $app): void
    {
    }

    public function register(ContainerContract $app): void
    {
        $app->instance(ChatlogConfig::class, $this);

        $app->singleton(JwtFactory::class);
        $app->singleton(ChatlogUserRepo::class);
        $app->singleton(ChatlogMessageRepo::class);
    }


}