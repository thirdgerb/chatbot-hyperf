<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;


use Commune\Support\Arr\ArrayAndJsonAble;

interface ResponseProtocal extends ArrayAndJsonAble
{
    public function getEvent() : string;
}