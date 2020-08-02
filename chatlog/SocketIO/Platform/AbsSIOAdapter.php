<?php


namespace Commune\Chatlog\SocketIO\Platform;


use Commune\Blueprint\Kernel\Protocals\AppRequest;
use Commune\Blueprint\Kernel\Protocals\AppResponse;
use Commune\Blueprint\Kernel\Protocals\ShellInputRequest;
use Commune\Blueprint\Kernel\Protocals\ShellOutputResponse;
use Commune\Blueprint\Platform\Adapter;
use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Kernel\Protocals\IShellInputRequest;
use Commune\Message\Intercom\IInputMsg;
use Commune\Protocals\HostMsg;
use Commune\Protocals\IntercomMsg;
use Commune\Support\Utils\TypeUtils;

abstract class AbsSIOAdapter implements Adapter
{

    /**
     * @var ChatlogWebPacker
     */
    protected $packer;

    /**
     * @var ShellInputRequest|null
     */
    protected $shellRequest;

    /**
     * @var string
     */
    protected $appId;

    /**
     * @var string|null
     */
    protected $invalid = null;


    /**
     * SIOAdapter constructor.
     * @param ChatlogWebPacker $packer
     * @param string $appId
     */
    public function __construct(
        ChatlogWebPacker $packer,
        string $appId
    )
    {
        $this->packer = $packer;
        $this->appId = $appId;
    }


    abstract public function parseHostMsg(ChatlogMessage $message): ? HostMsg;

    abstract public function filterIntercomMessage(IntercomMsg $message): bool;

    abstract public function parseMessageMode(IntercomMsg $message): int;

    abstract public function isContextOutput(IntercomMsg $message): bool;

    abstract public function parseContextOutput(IntercomMsg $message): array;

    abstract public function isDirective(IntercomMsg $message): bool;

    abstract public function parseDirective(array $directives, IntercomMsg $message): array;

    abstract public function hasSuggestions(IntercomMsg $message): bool;

    abstract public function parseSuggestions(IntercomMsg $message): array;

    abstract public function renderMessage(IntercomMsg $message): array;

    public function isInvalidRequest(): ? string
    {
        if (isset($this->invalid)) {
            return $this->invalid;
        }

        $batch = $this->packer->input;
        $user = $this->packer->user;

        if (empty($user)) {
            return 'user info is required';
        }

        if (count($batch->messages) !== 1) {
            return 'input message is invalid';
        }

        $hostMessage = $this->parseHostMsg($batch->messages[0]);

        if (empty($hostMessage)) {
            return 'message type is not supported';
        }

        $input = IInputMsg::instance(
            $hostMessage,
            $batch->session,
            $user->id,
            $user->name,
            '',
            null,
            $batch->batchId,
            $batch->scene
        );

        $this->shellRequest = IShellInputRequest::instance(
            false,
            $input,
            $this->packer->entry ?? '',
            $this->packer->env ?? [],
            $this->packer->comprehension,
            $this->packer->trace
        );
        return null;
    }

    public function getRequest(): AppRequest
    {
        $this->isInvalidRequest();
        return $this->shellRequest;
    }

    /**
     * @param ShellOutputResponse $response
     */
    public function sendResponse(AppResponse $response): void
    {
        // 类型检查.
        if (!$response instanceof ShellOutputResponse) {
            $expect = ShellOutputResponse::class;
            $given = TypeUtils::getType($response);
            $this->packer->fail("invalid response from chatbot, expect $expect, $given given");
            return;
        }

        $this->digestResponse($response);
        return;
    }


    protected function digestResponse(ShellOutputResponse $response) : array
    {
        $outputs = $response->getOutputs();

        // 准备输出参数
        $batches = [];
        $directives = [];

        // 准备中间参数.
        $batch = null;
        $creatorId = null;
        $batchId = null;
        $lastMode  = null;

        foreach ($outputs as $output) {
            // 按机制进行过滤处理.
            if (!$this->filterIntercomMessage($output)) {
                continue;
            }
            // 先确定消息的类型.
            $mode = $this->parseMessageMode($output);

            // 开启一个新的 batch.
            if (
                empty($batch) // 这是第一个 batch
                || $mode === MessageBatch::MODE_SYSTEM    //系统消息都独立一条.
                || $lastMode === MessageBatch::MODE_SYSTEM // 上一条已经是系统消息了.
                || $output->getCreatorId() !== $creatorId // 不是同一个 creator
            ) {
                $batch = new MessageBatch([
                    'mode' => $mode,
                    'scene' => $output->getScene(),
                    'session' => $response->getSessionId(),
                    'batchId' => $output->getMessageId(), // 用第一条消息的 id 作为batchId
                    'creatorId' => $output->getCreatorId(),
                    'creatorName' => $output->getCreatorName(),
                    'createdAt' => $output->getDeliverAt(),
                ]);
            }

            $lastMode = $mode;

            // 只保留最后一个 context.
            if (
                $mode === MessageBatch::MODE_BOT
                && $this->isContextOutput($output)
            ) {
                $batch->context = $this->parseContextOutput($output);
                continue;
            }

            // 指令另外保存.
            if ($this->isDirective($output)) {
                $directives = $this->parseDirective($directives, $output);
                continue;
            }

            // 保留最后一个 suggestions
            if ($this->hasSuggestions($output)) {
                $batch->suggestions = $this->parseSuggestions($output);
            }

            $rendered = $this->renderMessage($output);
            $batch->addMessages(...$rendered);
            $batches[$batch->batchId] = $batch;
        }

        // 发送响应. 采取广播的方式.
        $this->packer->outputBatches = array_values($batches);
        return [];
    }


    public function destroy(): void
    {
        unset(
            $this->packer,
            $this->shellRequest
        );
    }


}