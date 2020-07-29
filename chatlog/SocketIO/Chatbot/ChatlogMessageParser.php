<?php


namespace Commune\Chatlog\SocketIO\Chatbot;


use Commune\Blueprint\Kernel\Protocals\ShellOutputResponse;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Chatlog\SocketIO\Protocal\Directive;
use Commune\Protocals\HostMsg;
use Commune\Protocals\HostMsg\IntentMsg;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Protocals\IntercomMsg;

/**
 * Chatlog SocketIO 端和 CommuneChatbot 同步通信的桥梁.
 */
interface ChatlogMessageParser
{

    /**
     * @return ChatlogInputPacker
     */
    public function getPacker() : ChatlogInputPacker;

    /*----------- 输入检查 ------------*/

    /**
     * 判断输入消息是否合法,
     *
     * @return null|string
     */
    public function validatePacker() : ? string;

    /**
     * @return bool
     */
    public function hasRequest() : bool;

    /*----------- 输入转化 ------------*/

    /**
     * @return HostMsg
     */
    public function parseHostMsg() : HostMsg;

    /**
     * @return array
     */
    public function parseEnv() : array;

    /**
     * @return string
     */
    public function parseEntry() : string;

    /*----------- 输出处理. ------------*/

    /**
     * 如果返回 callable 对象, 会终止后续逻辑而执行 callable.
     * @param IntentMsg $intent
     * @param ShellOutputResponse $response
     * @return callable|null
     */
    public function acknowledgeIntent(
        IntentMsg $intent,
        ShellOutputResponse $response
    ) : ? callable ;


    /**
     * 过滤掉不支持的消息.
     * @param IntercomMsg $message
     * @return bool
     */
    public function filterIntercomMessage(IntercomMsg $message) : bool;

    /**
     * 确定消息的类型, 对应 Message Batch
     * @param IntercomMsg $message
     * @return int
     */
    public function parseMessageMode(IntercomMsg $message) : int;

    /**
     * 判断是否是 ContextMsg, 需要特殊处理.
     * @param IntercomMsg $message
     * @return bool
     */
    public function isContextOutput(IntercomMsg $message) : bool;

    /**
     * 转化 ContextMsg 为客户端需要的数组.
     * @param IntercomMsg $message
     * @return array
     */
    public function parseContextOutput(IntercomMsg $message) : array;

    /**
     * 是否是一个客户端命令.
     * @param IntercomMsg $message
     * @return bool
     */
    public function isDirective(IntercomMsg $message) : bool;

    /**
     * 将当前消息转变为客户端命令, 合并进已有的命令.
     * @param Directive[] $directives
     * @param IntercomMsg $message
     * @return Directive[]
     */
    public function parseDirective(array $directives, IntercomMsg $message) : array;


    /**
     * 是否有 suggestions.
     * 拥有 suggestions 并不一定就是 question
     *
     * @param IntercomMsg $message
     * @return bool
     */
    public function hasSuggestions(IntercomMsg $message) : bool;

    /**
     * 获取当前消息的 suggestions.
     * @param IntercomMsg $message
     * @return array
     */
    public function parseSuggestions(IntercomMsg $message) : array;

    /**
     * 将 IntercomMsg 变为 chatlog 客户端的 Message.
     * @param IntercomMsg $message
     * @return ChatlogMessage[]
     */
    public function renderMessage(IntercomMsg $message) : array;


}