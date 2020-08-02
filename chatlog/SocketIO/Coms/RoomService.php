<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Blueprint\Framework\ProcContainer;
use Commune\Blueprint\Platform\Packer;
use Commune\Chatlog\ChatlogConfig;
use Commune\Chatlog\SocketIO\Coms\Room\EntryParser;
use Commune\Chatlog\SocketIO\Coms\Room\InputParser;
use Commune\Chatlog\SocketIO\Coms\Room\OnDirective;
use Commune\Chatlog\SocketIO\Coms\Room\OnInput;
use Commune\Chatlog\SocketIO\Coms\Room\OnIntent;
use Commune\Chatlog\SocketIO\Coms\Room\OnOutput;
use Commune\Chatlog\SocketIO\Coms\Room\OnPacker;
use Commune\Chatlog\SocketIO\DTO\ChatInfo;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Container\ContainerContract;
use Commune\Contracts\Log\ConsoleLogger;

/**
 * 房间管理.
 */
class RoomService
{
    /**
     * @var ChatlogConfig
     */
    protected $config;

    /**
     * @var RoomOption[]
     */
    protected $roomMap = [];

    /**
     * @var ConsoleLogger
     */
    protected $console;

    /**
     * @var ContainerContract
     */
    protected $container;

    /**
     * RoomService constructor.
     * @param ProcContainer $container
     * @param ChatlogConfig $config
     * @param ConsoleLogger $console
     */
    public function __construct(
        ProcContainer $container,
        ChatlogConfig $config,
        ConsoleLogger $console
    )
    {
        $this->container = $container;
        $this->config = $config;
        $this->console = $console;

        foreach ($this->config->rooms as $room) {
            $this->addRoom($room);
        }
    }

    public function addRoom(RoomOption $room)
    {
        if (array_key_exists($room->scene, $this->roomMap)) {
            $this->console->warning(
                __METHOD__
                . ' redundant room option',
                [
                    'exists' => $this->roomMap[$room->scene]->toArray(),
                    'new' => $room->toArray(),
                ]
            );
        }

        $this->roomMap[$room->scene] = $room;
    }

    /**
     * 根据场景找到房间.
     *
     * @param string $scene
     * @return RoomOption|null
     */
    public function findRoom(string $scene) : ? RoomOption
    {
        return $this->roomMap[$scene] ?? null;
    }

    /**
     * 用户是否是超级管理员.
     *
     * @param UserInfo $user
     * @return bool
     */
    public function isSupervisor(UserInfo $user) : bool
    {
        return $user->level === Supervise::SUPERVISOR;
    }

    /**
     * 房间是否被监视
     * 如果为真, 所有的对话应该主动通知给客服, 让客服可以随时响应.
     *
     * @param string $scene
     * @return bool
     */
    public function isRoomSupervised(string $scene) : bool
    {
        $room = $this->findRoom($scene);
        return isset($room) && $room->supervised;
    }

    /**
     * 验证一个用户是否有访问房间的权力.
     *
     * @param string $scene
     * @param UserInfo $user
     * @param string|null $session
     * @return bool
     */
    public function verifyUser(
        string $scene,
        UserInfo $user,
        string $session = null
    ) : bool
    {
        $room = $this->findRoom($scene);
        // 不存在的房间不给加入.
        if (empty($room)) {
            return false;
        }

        // 超级管理员什么房间都允许加入.
        if ($this->isSupervisor($user)) {
            return true;
        }

        // 没有房间资格, 怎么样都不允许加入.
        if (!$this->roomMatchUser($room, $user)) {
            return false;
        }

        // 私人房间必须要 session 一致.
        if (isset($session) && $room->private) {
            $except = $this->makeSessionId($room, $user);
            return $except === $session;
        }

        return true;
    }

    /**
     * 用户是否符合预定义的权限.
     * 暂时不做更复杂的权限设计.
     *
     * @param RoomOption $room
     * @param UserInfo $user
     * @return bool
     */
    public function roomMatchUser(RoomOption $room, UserInfo $user) : bool
    {
        $mode = $room->levelMode;
        $level = $user->level;
        $roomLevel = $room->level;

        switch($mode) {
            case RoomOption::LEVEL_MODE_EXACTLY :
                return $level === $roomLevel;
            case RoomOption::LEVEL_MODE_ABOVE;
                return $level >= $roomLevel;
            case RoomOption::LEVEL_MODE_BELOW;
                return $level <= $roomLevel;
            default:
                return false;
        }
    }

    /**
     * 获取符合用户身份的所有预定义房间.
     *
     * @param UserInfo $user
     * @return RoomOption[]
     */
    public function roomsFor(UserInfo $user) : array
    {
        return array_values(array_filter(
            $this->roomMap,
            function(RoomOption $room) use ($user){
                return $this->roomMatchUser($room, $user);
            }
        ));
    }

    /**
     * 用户应该自动加入的房间.
     *
     * @param UserInfo $user
     * @return RoomOption[]
     */
    public function autoJoinRoomsFor(UserInfo $user) : array
    {
        return array_values(array_filter(
            $this->roomMap,
            function(RoomOption $room) use ($user){
                return $room->autoJoin && $this->roomMatchUser($room, $user);
            }
        ));

    }

    /**
     * 对于用户主动推荐的房间.
     *
     * @param UserInfo $user
     * @return RoomOption[]
     */
    public function recommendRoomsFor(UserInfo $user) : array
    {
        return array_values(array_filter(
            $this->roomMap,
            function(RoomOption $room) use ($user){
                return !$room->autoJoin
                    && $room->recommend
                    && $this->roomMatchUser($room, $user);
            }
        ));
    }

    /**
     * 生成 sessionId.
     *
     * @param RoomOption $room
     * @param UserInfo $user
     * @return string
     */
    public function makeSessionId(RoomOption $room, UserInfo $user) : string
    {
        $scene = $room->scene;

        // 超级管理员所在的场景是唯一的, 不根据用户身份来生成 sessionId.
        if ($this->isSupervisorScene($scene)) {
            return $this->getSupervisorSession();
        }

        // 设置了 session 的公共的房间, 与预计的相同.
        if (!$room->private) {
            return $room->scene;
        }

        // 否则生成一个私人的房间
        $secret = $this->config->jwtSecret;
        $id = $user->id;
        return sha1("scene:$scene:user:$id:salt:$secret");
    }


    /*---- 超管相关 ----*/

    public function isSupervisorScene(string $scene) : bool
    {
        return $scene === $this->config->supervisorScene;
    }

    /**
     * 获取超级管理员的房间.
     * @return RoomOption
     */
    public function getSupervisorRoom() : RoomOption
    {
        return $this->findRoom($this->config->supervisorScene);
    }

    public function getSupervisorSession() : string
    {
        $room = $this->getSupervisorRoom();
        $scene = $room->scene;
        $secret = $this->config->jwtSecret;
        return sha1("supervisor:scene:$scene:salt:$secret");
    }

    /*------- 生成房间的信息给用户 -------*/

    /**
     * 创建一个房间信息.
     *
     * @param RoomOption $option
     * @param UserInfo $user
     * @param bool $autoJoin
     * @param bool $toSupervisor
     * @return ChatInfo
     */
    public function createChatInfo(
        RoomOption $option,
        UserInfo $user,
        bool $toSupervisor = false
    ) : ChatInfo
    {
        if ($toSupervisor) {
            $title = $user->name . ':' . $option->title;
        } else {
            $title = $option->title;
        }

        return new ChatInfo([
            'title' => $title,
            'scene' => $option->scene,
            'icon' => $option->icon,
            'session' => $this->makeSessionId($option, $user),
            'closable' => $toSupervisor || $option->closable,
            'autoJoin' => !$toSupervisor && $option->autoJoin ,
            'bot' => $option->bot,
        ]);
    }

    /*--------- parser ----------*/

    public function onPacker(RoomOption $room) : OnPacker
    {
        $onPacker = $room->onPacker;
        return $this->container->make($onPacker);
    }

    public function onInput(RoomOption $room) : OnInput
    {
        $onInput = $room->onInput;
        return $this->container->make($onInput);
    }

    public function onOutput(RoomOption $room) : OnOutput
    {
        return $this->container->make($room->onOutput);
    }

//    public function onDirective(RoomOption $room) : ? OnDirective
//    {
//        $onDirective = $room->onDirective;
//        return empty($onDirective)
//            ? null
//            : $this->container->make($onDirective);
//    }
//
//    public function onIntent(RoomOption $room) : ? OnIntent
//    {
//        $onIntent = $room->onIntent;
//
//        return empty($onIntent)
//            ? null
//            : $this->container->make($onIntent);
//    }


}