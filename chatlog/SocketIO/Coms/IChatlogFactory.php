<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Commune\Blueprint\Framework\ProcContainer;

class IChatlogFactory implements ChatlogFactory
{
    use ChatlogFactoryTrait;


    public function __construct(ProcContainer $container)
    {
        $this->container = $container;
    }
}