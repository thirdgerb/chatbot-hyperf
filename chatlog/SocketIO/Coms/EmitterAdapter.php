<?php


namespace Commune\Chatlog\SocketIO\Coms;


use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;
use Hyperf\SocketIOServer\SocketIO;

class EmitterAdapter
{
    protected $emitter;

    /**
     * Emitter constructor.
     * @param Socket|BaseNamespace|SocketIO $emitter
     */
    public function __construct($emitter)
    {
        $this->emitter = $emitter;
    }


    /**
     * @param int|string $room
     * @return self
     */
    public function to($room): self
    {
        $this->emitter = $this->emitter->to($room);
        return $this;
    }


    /**
     * @param string $event
     * @param mixed[] ...$data
     * @return void
     */
    public function emit(string $event, ...$data) : void
    {
        $this->emitter->emit($event, ...$data);
    }
}