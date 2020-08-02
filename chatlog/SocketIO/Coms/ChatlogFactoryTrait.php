<?php


namespace Commune\Chatlog\SocketIO\Coms;

use Commune\Blueprint\Framework\ProcContainer;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\Database\ChatlogUserRepo;
use Commune\Chatlog\ChatlogConfig;

trait ChatlogFactoryTrait
{

    /**
     * @var ChatlogConfig|null
     */
    protected $config;

    /**
     * @var ProcContainer
     */
    protected $container;

    public function getConfig() : ChatlogConfig
    {
        return $this->config
            ?? $this->config = $this->container->make(ChatlogConfig::class);
    }

    public function getMessageRepo() : ChatlogMessageRepo
    {
        return $this
            ->container
            ->make(ChatlogMessageRepo::class);
    }


    public function getUserRepo() : ChatlogUserRepo
    {
        return $this
            ->container
            ->make(ChatlogUserRepo::class);
    }

    public function getJwtFactory() : JwtFactory
    {
        return $this
            ->container
            ->make(JwtFactory::class);
    }

    protected $_roomService;

    public function getRoomService() : RoomService
    {
        return $this->_roomService
            ?? $this->_roomService = $this
            ->container
            ->make(RoomService::class);
    }


}