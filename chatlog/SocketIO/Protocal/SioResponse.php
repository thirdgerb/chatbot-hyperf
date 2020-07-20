<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Blueprint\Exceptions\CommuneErrorCode;
use Commune\Support\Message\AbsMessage;

/**
 * @property-read string $event
 * @property-read string $trace
 * @property-read \Commune\Chatlog\SocketIO\Protocal\ResponseProtocal|null $proto
 */
class SioResponse extends AbsMessage implements CommuneErrorCode
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


}