<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;

use Commune\Blueprint\Exceptions\CommuneErrorCode;
use Commune\Support\Arr\ArrayAndJsonAble;
use Hyperf\SocketIOServer\Socket;


/**
 * @property-read string $event
 * @property-read string $trace
 */
interface SioResponse extends ArrayAndJsonAble, CommuneErrorCode
{

    public function toEmit(): array;

    public function emit(Socket $socket) : void;

}