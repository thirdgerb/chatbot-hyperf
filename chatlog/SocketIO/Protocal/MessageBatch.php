<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;

/**
 * 消息批次.
 *
 * @property int $mode
 * @property string $session
 * @property string $batchId
 * @property string $creatorId
 * @property string $creatorName
 * @property ChatlogMessage[] $messages
 * @property array $context
 * @property array $suggestions
 * @property int $createdAt
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
            'context' => [],
            'suggestions' => [],
            'messages' => [
            ],
            'createdAt' => 0
        ];
    }

    public static function fromInput(
        InputInfo $input,
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


    public function addMessages(ChatlogMessage ...$messages) : void
    {
        $this->_data['messages'] = array_merge(
            $this->_data['messages'],
            $messages
        );
    }

    public static function relations(): array
    {
        return [
            'messages[]' => ChatlogMessage::class
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

    public function shouldSave() : bool
    {
        return $this->mode !== self::MODE_SYSTEM
            && !empty($this->_data['messages']);
    }

    public function toSavableData() : array
    {
        $data = $this->_data;
        if (empty($data['messages'])) {
            return $data;
        }

        $messages = [];
        foreach ($this->messages as $key => $message) {
            if ($message->isSavable()) {
                $messages[$key] = $message;
            }
        }

        $data['messages'] = $messages;
        return $data;
    }

    public function toTransferArr(): array
    {
        if ($this->isEmpty()) {
            return parent::toTransferArr();
        }

        $backup = $this->_data;

        // 有些消息类型不用存储.
        $savable = $this->toSavableData();

        $this->_data = $savable;
        $serialized = parent::toTransferArr();
        $this->_data = $backup;
        return $serialized;
    }

}