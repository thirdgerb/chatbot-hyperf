<?php


namespace Commune\Chatbot\Hyperf\Coms\SocketIO;

use Commune\Support\Arr\ArrayAndJsonAble;


/**
 * @property-read string $event
 * @property-read string $trace
 * @property-read string $token
 * @property-read array $proto
 */
interface SioRequest extends ArrayAndJsonAble
{

    public function with(string $key, $value) : self;

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getTemp(string $key);

    public function makeResponse(ResponseProtocal $protocal) : SioResponse;
}