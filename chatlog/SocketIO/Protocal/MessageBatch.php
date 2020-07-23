<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatlog\SocketIO\Messages\Message;

/**
 * 消息批次.
 *
 * @property-read int $mode
 * @property-read string $session
 * @property-read string $batchId
 * @property-read string $creatorId
 * @property-read string $creatorName
 * @property-read Message[] $messages
 * @property-read int $createdAt
 */
class MessageBatch extends ChatlogResProtocal
{
    const MODE_BOT = 1;
    const MODE_USER = 2;
    const MODE_SYSTEM = 3;

    public static function stub(): array
    {
        return [
            'mode' => self::MODE_BOT,
            'session' => '',
            'batchId' => '',
            'creatorId' => '',
            'creatorName' => '',
            'messages' => [
            ],
            'createdAt' => 0
        ];
    }

    public static function fromInput(
        Input $input,
        UserInfo $user
    ) : MessageBatch
    {
        return new static([
            'mode' => self::MODE_USER,
            'session' => $input->session,
            'batchId' => $input->message->id,
            'creatorId' => $user->id,
            'creatorName' => $user->name,
            'messages' => [
                $input->message,
            ],
            'createdAt' => $input->createdAt,
        ]);
    }

    public static function fromSystem(string $session, Message $message) : MessageBatch
    {
        return new static([
            'mode' => self::MODE_SYSTEM,
            'session' => $session,
            'batchId' => $message->id,
            'creatorId' => '',
            'creatorName' => '',
            'messages' => [
                $message,
            ],
        ]);
    }

    public static function relations(): array
    {
        return [
            'messages[]' => Message::class
        ];
    }

    public function isEmpty(): bool
    {
        return empty($this->messages);
    }

    public function getEvent(): string
    {
        return 'MESSAGE_BATCH';
    }


}