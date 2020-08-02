<?php


namespace Commune\Chatlog\SocketIO\Platform;

use Commune\Blueprint\Platform\Adapter;
use Commune\Blueprint\Platform\Packer;
use Commune\Chatlog\SocketIO\Coms\EmitterAdapter;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Protocals\Comprehension;
use Commune\Protocals\HostMsg\IntentMsg;

class ChatlogWebPacker implements Packer
{

    /**
     * @var EmitterAdapter
     */
    public $emitter;

    /**
     * @var string 
     */
    public $trace;
    
    /**
     * @var MessageBatch|null
     */
    public $input;

    /**
     * @var UserInfo|null
     */
    public $user;

    /**
     * @var string|null
     */
    public $entry = null;

    /**
     * @var array|null
     */
    public $env = null;

    /**
     * @var Comprehension|null
     */
    public $comprehension = null;

    /*------- 输出数据 -------*/

    /**
     * @var MessageBatch[]
     */
    public $outputBatches = [];

    public function __construct(
        EmitterAdapter $emitter,
        string $trace,
        UserInfo $user = null,
        MessageBatch $input = null
    )
    {
        $this->trace = $trace;
        $this->emitter = $emitter;
        $this->input = $input;
        $this->user = $user;
    }


    public function isInvalidInput(): ? string
    {
        if (
            empty($this->input)
            || empty($this->user)
        ) {
            return "is invalid request";
        }
        return null;
    }

    public function adapt(string $adapterName, string $appId): Adapter
    {
        return new $adapterName($this, $appId);
    }

    public function fail(string $error): void
    {
        $this->emitter->emit(
            'error',
            $error
        );
    }

    public function destroy(): void
    {
        unset(
            $this->outputBatches,
            $this->emitter,
            $this->trace,
            $this->input,
            $this->user,
            $this->entry,
            $this->env,
            $this->comprehension
        );
    }


}