<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Support\Message\AbsMessage;

abstract class ResponseProtocal extends AbsMessage
{
    abstract public function getEvent() : string;
}