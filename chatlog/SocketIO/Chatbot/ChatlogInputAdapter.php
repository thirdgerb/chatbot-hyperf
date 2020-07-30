<?php


namespace Commune\Chatlog\SocketIO\Chatbot;


use Commune\Chatlog\SocketIO\Messages\EventMessage;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Message\Host\Convo\IEventMsg;
use Commune\Message\Host\Convo\IText;
use Commune\Message\Host\Convo\Verbal\JsonMsg;
use Commune\Protocals\HostMsg;
use Commune\Protocals\HostMsg\IntentMsg;
use Commune\Protocals\Intercom\OutputMsg;
use Commune\Protocals\IntercomMsg;
use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Blueprint\Kernel\Protocals\ShellOutputResponse;
use Commune\Support\Utils\MarkdownUtils;

class ChatlogInputAdapter extends AbsSIOAdapter implements ChatlogMessageParser
{
    public function getParser(): ChatlogMessageParser
    {
        return $this;
    }

    public function getPacker(): ChatlogInputPacker
    {
        return $this->packer;
    }

    public function validatePacker(): ? string
    {
        return null;
    }

    public function hasRequest(): bool
    {
        return isset($this->packer->request);
    }

    public function parseHostMsg(): HostMsg
    {
        $message = $this->packer->input->message;

        if ($message instanceof TextMessage) {
            return IText::instance($message->text);
        }

        if ($message instanceof EventMessage) {
            return IEventMsg::instance($message->name, $message->payload);
        }


        // 无法理解消息, 就发送一个通用事件去好了.
        $name = HostMsg\Convo\EventMsg::EVENT_CLIENT_ACKNOWLEDGE;
        return IEventMsg::instance($name);
    }


    public function parseEnv(): array
    {
        return [];
    }

    public function parseEntry() : string
    {
        $packer = $this->getPacker();
        $roomService = $packer->factory->getRoomService();

        return $roomService->parseEntry(
            $packer->input,
            $packer->user
        );
    }

    public function acknowledgeIntent(
        IntentMsg $intent,
        ShellOutputResponse $response
    ): ? callable
    {
        // 目前还没想到有哪些需要特别处理的 intent.
        // 未来肯定会有. 比如将谁踢出房间之类的.
        return null;
    }

    public function filterIntercomMessage(IntercomMsg $message): bool
    {
        $hostMsg = $message->getMessage();
        return $hostMsg instanceof HostMsg\Convo\VerbalMsg
            || $hostMsg instanceof HostMsg\IntentMsg
            || $hostMsg instanceof HostMsg\Convo\EventMsg;
        // todo 还有 视频 / 命令等.
    }

    public function parseMessageMode(IntercomMsg $message): int
    {
        if ($message->getMessage() instanceof HostMsg\Convo\EventMsg) {
            return MessageBatch::MODE_SYSTEM;
        }

        return $message instanceof OutputMsg
            ? MessageBatch::MODE_BOT
            : MessageBatch::MODE_USER;
    }

    public function isContextOutput(IntercomMsg $message): bool
    {
        $hostMsg = $message->getMessage();
        return $hostMsg instanceof HostMsg\Convo\ContextMsg;
    }

    public function parseContextOutput(IntercomMsg $message): array
    {
        $contextMsg = $message->getMessage();
        if (!$contextMsg instanceof HostMsg\Convo\ContextMsg) {
            return [];
        }
        return [
            'id' => $contextMsg->getContextId(),
            'name' => $contextMsg->getContextName(),
            'stage' => $contextMsg->getStageName(),
            'query' => $contextMsg->getQuery(),
            'data' => $contextMsg->getMemorableData(),
        ];
    }

    public function isDirective(IntercomMsg $message): bool
    {
        // todo 回头再实现命令.
        return false;
    }

    public function parseDirective(array $directives, IntercomMsg $message): array
    {
        return [];
    }

    public function hasSuggestions(IntercomMsg $message): bool
    {
        $hostMsg = $message->getMessage();
        return $hostMsg instanceof HostMsg\Convo\QA\QuestionMsg
            || $hostMsg instanceof HostMsg\Tags\Conversational;
    }

    public function parseSuggestions(IntercomMsg $message): array
    {
        $hostMsg = $message->getMessage();
        return $hostMsg instanceof HostMsg\Tags\Conversational
            ? $hostMsg->getSuggestions()
            : [];
    }

    public function renderMessage(IntercomMsg $message): array
    {
        $hostMsg = $message->getMessage();
        $id = $message->getMessageId();

        if ($hostMsg instanceof JsonMsg) {
            return [
                new TextMessage([
                    'id' => $id,
                    'text' => MarkdownUtils::quote($hostMsg->getText()),
                ])
            ];
        }

        if ($hostMsg instanceof HostMsg\Convo\QA\QuestionMsg) {
            return [
              new TextMessage([
                  'id' => $id,
                  'text' => $hostMsg->getQuery(),
              ])
            ];
        }

        if ($hostMsg instanceof HostMsg\Convo\VerbalMsg) {
            return [
                new TextMessage([
                    'id' => $id,
                    'text' => $hostMsg->getText(),
                ])
            ];
        }

        if ($hostMsg instanceof IntentMsg) {
            return [
                new TextMessage([
                    'id' => $id,
                    'text' => $hostMsg->getText(),
                ]),
            ];
        }

        return [];
    }


}