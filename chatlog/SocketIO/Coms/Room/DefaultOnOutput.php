<?php


namespace Commune\Chatlog\SocketIO\Coms\Room;


use Commune\Blueprint\Shell;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Chatlog\SocketIO\Platform\ChatlogWebPacker;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioResponse;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;

class DefaultOnOutput implements OnOutput
{

    public function __invoke(
        RoomOption $room,
        ChatlogWebPacker $packer,
        MessageBatch $batch
    ) : ? MessageBatch
    {
        $name = $room->botName;

        if (!empty($name) && $batch->mode === MessageBatch::MODE_BOT) {
            $batch->creatorName = $name;
        }

        // 群发消息.
        $response = new ChatlogSioResponse([
            'event' => $batch->getEvent(),
            'trace' => $packer->trace,
            'proto' => $batch,
        ]);

        $packer->emitter
            ->to($batch->session)
            ->emit($response->event, $response->toEmit());

        return $batch;
    }


}