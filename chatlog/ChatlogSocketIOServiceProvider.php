<?php


namespace Commune\Chatlog;


use Commune\Blueprint\CommuneEnv;
use Commune\Blueprint\Exceptions\CommuneLogicException;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\SocketIO\Chatbot\ChatlogInputAdapter;
use Commune\Chatlog\SocketIO\Coms\JwtFactory;
use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\Handlers;
use Commune\Container\ContainerContract;
use Commune\Contracts\ServiceProvider;
use Commune\Support\Utils\StringUtils;
use Lcobucci\JWT\Signer\Hmac\Sha256;

/**
 *
 * @property-read string $appName               应用的名称.
 * @property-read bool $debug
 *
 * ## 机器人相关.
 *
 * @property-read string $adapterName           adapter
 *
 * ## jwt 相关
 * @property-read string $jwtSigner             Jwt Signer 类名
 * @property-read string $jwtSecret             Jwt 密钥
 * @property-read int $jwtExpire   seconds      Jwt 过期时间.
 *
 * ## socket io 事件协议.
 *
 *
 * @property-read string[] $protocals           协议与 handler 列表. $eventName => $eventHandlerName
 *
 * ## io
 *
 * @property-read string $dbConnection
 *
 * ## user
 * @property-read string $userHashSigner        用户密码加密的 signer
 * @property-read string $userHashSalt          用户密码加密的盐
 *
 *
 * ## 房间相关.
 * @property-read RoomOption[] $rooms           系统定义的房间.
 * @property-read string $supervisorScene       超管所在的房间场景.
 *
 *
 * @property-read string $roomOptionFile        用 PHP 数组记录 rooms 配置.
 */
class ChatlogSocketIOServiceProvider extends ServiceProvider
    implements ChatlogConfig
{
    /**
     * @var RoomOption[]|null
     */
    protected $_rooms;

    public static function stub(): array
    {
        return [
            'appName' => 'chatlog',
            'debug' => CommuneEnv::isDebug(),

            'adapterName' => ChatlogInputAdapter::class,

            'protocals' => [
                'SIGN' => Handlers\SignHandler::class,
                'REGISTER' => Handlers\RegisterHandler::class,
                'JOIN' => Handlers\JoinHandler::class,
                'USER_LOGOUT' => Handlers\UserLogoutHandler::class,
                'LEAVE' => Handlers\LeaveHandler::class,
                'INPUT' => Handlers\InputHandler::class,
                'MANUAL' => Handlers\ManualHandler::class,
                'QUERY_CHATS'=> Handlers\QueryChatsHandler::class,
                'QUERY_MESSAGES' => Handlers\QueryMessagesHandler::class,
                'QUERY_SCENE' => Handlers\QuerySceneHandler::class,
            ],

            'jwtSigner' => Sha256::class,
            'jwtSecret' => env('CHATLOG_JWT_SECRET', 'helple~~ss'),
            'jwtExpire' => 86400 * 7,

            'dbConnection' => 'default',

            'userHashSigner' => Sha256::class,
            'userHashSalt' => env('CHATLOG_USER_SALT', 'power overwhelming'),

            'supervisorScene' => 'supervisor',
            'roomOptionFile' => StringUtils::gluePath(
                CommuneEnv::getResourcePath(),
                'chatlog/rooms.php'
            ),
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
        // 初始化一下. 有 bug 就别启动了.
        $app->make(RoomService::class);
    }

    public function register(ContainerContract $app): void
    {
        $app->instance(ChatlogConfig::class, $this);

        $app->singleton(JwtFactory::class);
        $app->singleton(ChatlogUserRepo::class);
        $app->singleton(ChatlogMessageRepo::class);
        $app->singleton(RoomService::class);
    }

    /**
     * @param string $name
     * @return RoomOption[]
     */
    public function __get_rooms() : array
    {
        if (isset($this->_rooms)) {
            return $this->_rooms;
        }

        $file = $this->roomOptionFile;
        if (!file_exists($file)) {
            throw new CommuneLogicException(
                "room config file not exists"
            );
        }
        $data = include $file;

        $this->_rooms = [];
        foreach ($data as $optionData) {
            $this->_rooms[] = new RoomOption($optionData);
        }

        return $this->_rooms;
    }

}