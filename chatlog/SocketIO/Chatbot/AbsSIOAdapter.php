<?php


namespace Commune\Chatlog\SocketIO\Chatbot;


use Commune\Blueprint\Kernel\Protocals\AppRequest;
use Commune\Blueprint\Kernel\Protocals\AppResponse;
use Commune\Blueprint\Kernel\Protocals\ShellInputRequest;
use Commune\Blueprint\Kernel\Protocals\ShellOutputResponse;
use Commune\Blueprint\Platform\Adapter;
use Commune\Chatlog\SocketIO\Protocal\Directive;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Kernel\Protocals\IShellInputRequest;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Support\Utils\TypeUtils;

abstract class AbsSIOAdapter implements Adapter, ChatlogMessageParser
{

    /**
     * @var ChatlogSIOPacker
     */
    protected $packer;

    /**
     * @var ShellInputRequest
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
     * @param ChatlogSIOPacker $packer
     * @param string $appId
     */
    public function __construct(ChatlogSIOPacker $packer, string $appId)
    {
        $this->packer = $packer;
        $this->appId = $appId;

        $error = $this->validateInput($this->packer->input, $this->packer->user);
        if ($error) {
            $this->invalid = $error;
        } else {
            $input = $this->makeInputMsg($this->packer->$input, $this->packer->user);
            $this->shellRequest = $this->makeRequest($input);
        }
    }


    public function isInvalid(): ? string
    {
        return $this->invalid;
    }


    protected function makeRequest(InputMsg $input) : AppRequest
    {
        $env = $this->parseEnv(
            $this->packer->request,
            $this->packer->input,
            $this->packer->user
        );

        return IShellInputRequest::instance(
            false,
            $input,
            $this->packer->getEntry(),
            $env,
            null,
            $this->packer->request->trace
        );
    }

    public function getRequest(): AppRequest
    {
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

        // 意图处理.
        $intents = $response->getIntents();
        // 先接纳所有的意图.
        foreach ($intents as $intent) {
            $stop = $this->acknowledgeIntent($intent, $response);
            if ($stop) {
                $stop();
                return;
            }
        }

        // 处理输出消息.
        $this->handleShellOutputs($response);
    }

    protected function handleShellOutputs(ShellOutputResponse $response) : void
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
                empty($batch)
                || $lastMode === MessageBatch::MODE_SYSTEM
                || $mode === MessageBatch::MODE_SYSTEM    //系统消息都独立一条.
                || $output->getCreatorId() !== $creatorId
            ) {
                $batch = new MessageBatch([
                    'mode' => $mode,
                    'session' => $response->getSessionId(),
                    'batchId' => $output->getMessageId(),
                    'creatorId' => $output->getCreatorId(),
                    'creatorName' => $output->getCreatorName(),
                    'createdAt' => $output->getDeliverAt(),
                ]);
            }

            $lastMode = $mode;

            // 只保留最后一个 context.
            if ($this->isContextOutput($output)) {
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
        $this->deliverShellResponse($batches);

        // 发送命令给客户端自身.
        $this->deliverDirectives($directives);
    }


    /**
     * 发送消息.
     * @param MessageBatch[] $batches
     */
    public function deliverShellResponse(array $batches) : void
    {
        if (empty($batches)) {
            return;
        }

        $request = $this->packer->request;
        foreach ($batches as $batch) {

            $this->packer
                ->messageRepo
                ->saveBatch($batch);

            $response = $request->makeResponse($batch);

            $this
                ->packer
                ->emitter
                ->to($batch->session)
                ->emit(
                    $response->event,
                    $response->toEmit()
                );
        }

    }

    /**
     * 发送命令.
     * @param Directive[] $directives
     */
    public function deliverDirectives(array $directives) : void
    {
        if (empty($directives)) {
            return;
        }

        $request = $this->packer->request;
        $directives = array_map(function($directive) use ($request) {
            return $request->makeResponse($directive)->toEmit();
        }, $directives);

        $this->packer->socket->emit(Directive::EVENT, ...$directives);
    }

    public function destroy(): void
    {
        unset(
            $this->packer,
            $this->shellRequest
        );
    }


}