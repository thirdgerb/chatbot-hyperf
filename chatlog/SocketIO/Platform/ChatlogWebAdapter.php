<?php


namespace Commune\Chatlog\SocketIO\Platform;


use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Chatlog\SocketIO\Messages\EventMessage;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Message\Host\Convo\IEventMsg;
use Commune\Message\Host\Convo\IText;
use Commune\Message\Host\Convo\Verbal\JsonMsg;
use Commune\Message\Host\Convo\Verbal\MarkdownMsg;
use Commune\Protocals\HostMsg;
use Commune\Protocals\HostMsg\IntentMsg;
use Commune\Protocals\Intercom\OutputMsg;
use Commune\Protocals\IntercomMsg;
use Commune\Chatlog\SocketIO\Messages\TextMessage;
use Commune\Support\Markdown\MarkdownUtils;

class ChatlogWebAdapter extends AbsSIOAdapter
{

    public function parseHostMsg(ChatlogMessage $message): ? HostMsg
    {
        if ($message instanceof TextMessage) {
            return IText::instance($message->text);
        }

        if ($message instanceof EventMessage) {
            return IEventMsg::instance($message->name, $message->payload);
        }

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
                    'md' => true,
                ])
            ];
        }

        if ($hostMsg instanceof MarkdownMsg) {
            return [
                new TextMessage([
                    'id' => $id,
                    'text' => $hostMsg->getText(),
                    'md' => true,
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