<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatbot\Hyperf\Coms\SocketIO\ResponseProtocal;
use Commune\Support\Message\AbsMessage;

abstract class ChatlogResProtocal extends AbsMessage
    implements ResponseProtocal
{
}