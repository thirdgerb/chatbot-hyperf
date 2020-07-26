<?php


namespace Commune\Chatlog\SocketIO;

use Commune\Chatbot\Hyperf\Coms\SocketIO\SocketIOController;
use Commune\Chatlog\ChatlogConfig;

class ChatlogSocketIOController extends SocketIOController
{

    protected function registerHandlers(): void
    {
        $this->protocals = $this->container
            ->get(ChatlogConfig::class)
            ->protocals;

        parent::registerHandlers();
    }


}