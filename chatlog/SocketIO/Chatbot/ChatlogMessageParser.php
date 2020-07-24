<?php


namespace Commune\Chatlog\SocketIO\Chatbot;


use Commune\Blueprint\Kernel\Protocals\ShellOutputResponse;
use Commune\Chatbot\Hyperf\Coms\SocketIO\SioRequest;
use Commune\Chatlog\SocketIO\Messages\ChatlogMessage;
use Commune\Chatlog\SocketIO\Protocal\Input;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Commune\Protocals\HostMsg\IntentMsg;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Protocals\IntercomMsg;

interface ChatlogMessageParser
{


    public function makeInputMsg(
        Input $input,
        UserInfo $user
    ) : InputMsg;

    public function parseEnv(
        SioRequest $request,
        Input $input,
        UserInfo $user
    ) : array;

    /**
     * 判断输入消息是否合法,
     * @param Input $input
     * @param UserInfo $user
     * @return null|string
     */
    public function validateInput(Input $input, UserInfo $user) : ? string; /* error */

    public function filterIntercomMessage(IntercomMsg $message) : bool;

    public function parseMessageMode(IntercomMsg $message) : int;


    public function acknowledgeIntent(
        IntentMsg $intent,
        ShellOutputResponse $response
    ) : ? callable ;


    public function isContextOutput(IntercomMsg $message) : bool;

    public function parseContextOutput(IntercomMsg $message) : array;


    public function isDirective(IntercomMsg $message) : bool;

    public function parseDirective(array $directives, IntercomMsg $message) : array;


    public function hasSuggestions(IntercomMsg $message) : bool;

    public function parseSuggestions(IntercomMsg $message) : array;

    /**
     * @param IntercomMsg $message
     * @return ChatlogMessage[]
     */
    public function renderMessage(IntercomMsg $message) : array;


}