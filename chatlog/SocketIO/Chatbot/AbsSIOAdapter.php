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
use Commune\Message\Intercom\IInputMsg;
use Commune\Protocals\Comprehension;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Support\Utils\TypeUtils;

abstract class AbsSIOAdapter implements Adapter
{

    /**
     * @var ChatlogInputPacker
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
     * @param ChatlogInputPacker $packer
     * @param string $appId
     */
    public function __construct(
        ChatlogInputPacker $packer,
        string $appId
    )
    {
        $this->packer = $packer;
        $this->appId = $appId;

        $parser = $this->getParser();
        $error = $parser->validatePacker();

        if ($error) {
            $this->invalid = $error;

        } elseif($parser->hasRequest()) {
            $input = $this->makeInputMsg();
            $entry = $parser->parseEntry();
            $env = $parser->parseEnv();
            $this->shellRequest = $this->makeRequest($input, $entry, $env);
        }
    }


    abstract public function getParser() : ChatlogMessageParser;

    public function isInvalid(): ? string
    {
        return $this->invalid;
    }

    protected function makeInputMsg() : InputMsg
    {
        return IInputMsg::instance(
            $this->getParser()->parseHostMsg(),
            $this->packer->input->session,
            $this->packer->user->id,
            $this->packer->user->name,
            '',
            null,
            $this->packer->input->message->id
        );
    }

    protected function makeRequest(
        InputMsg $input,
        string $entry,
        array $env,
        Comprehension $comprehension = null
    ) : AppRequest
    {

        return IShellInputRequest::instance(
            false,
            $input,
            $entry,
            $env,
            $comprehension,
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
        $parser = $this->getParser();

        $logs = $this->acknowledgeIntents($parser, $response)
            ?? $this->handleOutputs($parser, $response);

        if (!empty($logs)) {
            $logger = $this->packer->platform->getLogger();
            foreach ($logs as list($level, $log)) {
                $logger->info($level, $log);
            }
        }

        return;
    }

    protected function acknowledgeIntents(
        ChatlogMessageParser $parser,
        ShellOutputResponse $response
    ) : ? array
    {
        // 意图处理.
        $intents = $response->getIntents();

        // 先接纳所有的意图.
        foreach ($intents as $intent) {
            $stop = $parser->acknowledgeIntent($intent, $response);
            if ($stop) {
                return $stop();
            }
        }
        return null;
    }

    protected function handleOutputs(
        ChatlogMessageParser $parser,
        ShellOutputResponse $response
    ) : array
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
            if (!$parser->filterIntercomMessage($output)) {
                continue;
            }
            // 先确定消息的类型.
            $mode = $parser->parseMessageMode($output);

            // 开启一个新的 batch.
            if (
                empty($batch) // 这是第一个 batch
                || $mode === MessageBatch::MODE_SYSTEM    //系统消息都独立一条.
                || $lastMode === MessageBatch::MODE_SYSTEM // 上一条已经是系统消息了.
                || $output->getCreatorId() !== $creatorId // 不是同一个 creator
            ) {
                $batch = new MessageBatch([
                    'mode' => $mode,
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
                && $parser->isContextOutput($output)
            ) {
                $batch->context = $parser->parseContextOutput($output);
                continue;
            }

            // 指令另外保存.
            if ($parser->isDirective($output)) {
                $directives = $parser->parseDirective($directives, $output);
                continue;
            }

            // 保留最后一个 suggestions
            if ($parser->hasSuggestions($output)) {
                $batch->suggestions = $parser->parseSuggestions($output);
            }

            $rendered = $parser->renderMessage($output);
            $batch->addMessages(...$rendered);
            $batches[$batch->batchId] = $batch;
        }

        // 发送响应. 采取广播的方式.
        $batches = $this->parseBatches($batches);
        $this->deliverShellResponse($batches);

        // 发送命令给客户端自身.
        $this->deliverDirectives($directives);
        return [];
    }

    /**
     * 对消息数据进行处理.
     * @param MessageBatch[] $batches
     * @return MessageBatch[]
     */
    public function parseBatches(array $batches) : array
    {
        $service = $this->packer->factory->getRoomService();
        $input = $this->packer->input;
        $room = $service->findRoom($input->scene);
        $botName = $room->botName;

        // 进行姓名处理.
        $batches = array_values(
            array_map(
                function(MessageBatch $message) use ($botName){
                    if (!empty($botName) && $message->mode === MessageBatch::MODE_BOT) {
                        $message->creatorName = $botName;
                    }
                    return $message;
                },
                $batches
            )
        );

        return $batches;
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
        $factory = $this->packer->factory;

        // 保存消息.
        $factory
            ->getMessageRepo()
            ->saveBatch($this->packer->shell->getId(), ...$batches);

        foreach ($batches as $batch) {
            // 群发响应. 而且是逐条发.
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

        // 命令只对自身有效.
        $socket = $this->packer->socket;
        foreach ($directives as $directive) {
            $request->makeResponse($directive)->emit($socket);
        }
    }

    public function destroy(): void
    {
        unset(
            $this->packer,
            $this->shellRequest
        );
    }


}