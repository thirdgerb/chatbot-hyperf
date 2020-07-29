<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Commune\Chatlog\ChatlogConfig;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\Database\ChatlogUserRepo;

interface ChatlogFactory
{
    public function getConfig() : ChatlogConfig;

    public function getMessageRepo() : ChatlogMessageRepo;

    public function getUserRepo() : ChatlogUserRepo;

    public function getJwtFactory() : JwtFactory;

    public function getRoomService() : RoomService;

}