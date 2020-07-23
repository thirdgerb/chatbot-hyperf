<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatbot\Hyperf\Coms\SocketIO\SioResponse;
use Commune\Support\Message\AbsMessage;
use Hyperf\SocketIOServer\Socket;

/**
 * @property-read string $event
 * @property-read string $trace
 * @property-read \Commune\Chatlog\SocketIO\Protocal\ChatlogResProtocal|null $proto
 */
class ChatlogSioResponse extends AbsMessage implements SioResponse
{
    public static function stub(): array
    {
        return [
            'event' => '',
            'trace' => '',
            'proto' => null,
        ];
    }

    public static function relations(): array
    {
        return [
        ];
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function toEmit(): array
    {
        $data = $this->toArray();
        unset($data['event']);
        return $data;
    }

    public function emit(Socket $socket) : void
    {
        $socket->emit($this->event, $this->toEmit());
    }


}