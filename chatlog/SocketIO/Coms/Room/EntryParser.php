<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;

/**
 * 生成一个 Ghost 识别的 Entry (context ucl)
 */
interface EntryParser
{
    /**
     * @param RoomOption $option
     * @param InputInfo $input
     * @param UserInfo $user
     * @return string
     */
    public function __invoke(
        RoomOption $option,
        InputInfo $input,
        UserInfo $user
    ) : string;

}