<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatlog\SocketIO\ChatlogConfig;
use Commune\Chatlog\SocketIO\Protocal\ChatInfo;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;

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
     * RoomService constructor.
     * @param ChatlogConfig $config
     */
    public function __construct(ChatlogConfig $config)
    {
        $this->config = $config;

        foreach ($this->config->rooms as $room) {
            $this->roomMap[$room->scene] = $room;
        }
    }


    /**
     * 根据场景找到房间.
     * @param string $scene
     * @return RoomOption|null
     */
    public function findRoom(string $scene) : ? RoomOption
    {
        return $this->roomMap[$scene] ?? null;
    }

    public function isSupervisor(UserInfo $user) : bool
    {
        return $user->level === Supervise::SUPERVISOR;
    }

    public function isRoomSupervised(string $scene) : bool
    {
        $room = $this->findRoom($scene);
        return isset($room) && $room->supervised;
    }

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

        if ($this->isSupervisor($user)) {
            return true;
        }

        if (!$this->roomMatchUser($room, $user)) {
            return false;
        }

        if (isset($session) && $room->private) {
            $except = $this->makeSessionId($room, $user);
            return $except === $session;
        }

        return true;
    }

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
     * @param UserInfo $user
     * @return RoomOption[]
     */
    public function roomsFor(UserInfo $user) : array
    {
        return array_filter($this->roomMap, function(RoomOption $room) use ($user){
            return $this->roomMatchUser($room, $user);
        });
    }

    /**
     * 用户应该自动加入的房间.
     *
     * @param UserInfo $user
     * @return RoomOption[]
     */
    public function autoJoinRoomsFor(UserInfo $user) : array
    {
        return array_filter($this->roomMap, function(RoomOption $room) use ($user){
            return $room->autoJoin && $this->roomMatchUser($room, $user);
        });

    }

    /**
     * 对于用户推荐的房间.
     *
     * @param UserInfo $user
     * @return RoomOption[]
     */
    public function recommendRoomsFor(UserInfo $user) : array
    {
        return array_filter($this->roomMap, function(RoomOption $room) use ($user){
            return !$room->autoJoin
                && $room->recommend
                && $this->roomMatchUser($room, $user);
        });
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

        // 管理员所在的场景不根据用户身份来生成 sessionId.
        if ($this->isSupervisorScene($scene)) {
            return $this->getSupervisorSession();
        }

        // 设置了 session 的公共的房间, 与预计的相同.
        $session = $room->session;
        if (!empty($session)) {
            return $session;
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

    public function getSupervisorRoom() : RoomOption
    {
        return $this->findRoom($this->config->supervisorScene);
    }

    public function getSupervisorSession() : string
    {
        $room = $this->getSupervisorRoom();
        $session = $room->session;
        $scene = $room->scene;
        $secret = $this->config->jwtSecret;
        return sha1("scene:$scene:default_session:$session:salt:$secret");
    }

    /*------- 生成房间的信息给用户 -------*/

    /**
     * @param RoomOption $option
     * @param UserInfo $user
     * @param bool $autoJoin
     * @param bool $toSupervisor
     * @return ChatInfo
     */
    public function createChatInfo(
        RoomOption $option,
        UserInfo $user,
        bool $autoJoin = false,
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
            'closable' => $option->closable,
            'autoJoin' => $autoJoin,
            'bot' => $option->bot,
        ]);
    }



}