<?php


namespace Commune\Chatlog\SocketIO\Protocal;


use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Support\Uuid\HasIdGenerator;
use Commune\Support\Uuid\IdGeneratorHelper;

/**
 * 消息批次.
 *
 * @property int $mode
 * @property string $scene
 * @property string $session
 * @property string $batchId
 * @property string $creatorId
 * @property string $creatorName
 * @property ChatlogMessage[] $messages
 * @property array $context
 * @property array $suggestions
 * @property int $createdAt
 */
class MessageBatch extends ChatlogResProtocal implements HasIdGenerator
{
    use IdGeneratorHelper;

    const MODE_BOT = 1;
    const MODE_USER = 2;
    const MODE_SYSTEM = 3;

    public static function stub(): array
    {
        return [
            'mode' => self::MODE_BOT,
            'scene' => '',
            'session' => '',
            'batchId' => '',
            'creatorId' => '',
            'creatorName' => '',
            'context' => [],
            'suggestions' => [],
            'messages' => [
            ],
            'createdAt' => intval(microtime(true) * 1000),
        ];
    }

    public static function fromSystem(string $sessionId, ChatlogMessage ...$messages) : self
    {
        return new static([
            'mode' => self::MODE_SYSTEM,
            'session' => $sessionId,
            'messages' => $messages,
        ]);
    }

    public static function fromInput(
        InputInfo $input,
        UserInfo $user
    ) : MessageBatch
    {
        return new static([
            'mode' => self::MODE_USER,
            'scene' => $input->scene,
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

    public function __set_batchId(string $name, $value) : void
    {
        $value = empty($value) ? $this->createUuId() : $value;
        $this->_data[$name] = $value;
    }

    public function isEmpty(): bool
    {
        return empty($this->messages);
    }

    public function getEvent(): string
    {
        return 'MESSAGE_BATCH';
    }

    protected $_savable;

    public function shouldSave() : bool
    {
        if (isset($this->_savable)) {
            return $this->_savable;
        }

        if ($this->mode === self::MODE_SYSTEM) {
            return $this->_savable = false;
        }

        $savables = array_filter($this->messages, function(ChatlogMessage $message) {
            return $message->shouldSave();
        });

        return $this->_savable = count($savables) > 0;
    }

    public function toSavableData() : array
    {
        $data = $this->_data;
        if (empty($data['messages'])) {
            return $data;
        }

        $messages = [];
        foreach ($this->messages as $key => $message) {
            if ($message->shouldSave()) {
                $messages[$key] = $message;
            }
        }

        $data['context'] = [];
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