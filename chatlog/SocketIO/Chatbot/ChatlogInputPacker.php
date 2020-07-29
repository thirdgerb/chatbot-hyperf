<?php


namespace Commune\Chatlog\SocketIO\Chatbot;


use Commune\Blueprint\Platform\Adapter;
use Commune\Blueprint\Platform\Packer;
use Commune\Blueprint\Shell;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;
use Commune\Chatlog\SocketIO\Coms\ChatlogFactory;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\DTO\InputInfo;
use Commune\Chatlog\SocketIO\DTO\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class ChatlogInputPacker implements Packer
{

    /**
     * @var InputInfo
     */
    public $input;

    /**
     * @var UserInfo
     */
    public $user;

    /**
     * @var Shell
     */
    public $shell;

    /**
     * @var Socket
     */
    public $socket;

    /**
     * @var ChatlogSioRequest
     */
    public $request;

    /**
     * @var BaseNamespace
     */
    public $emitter;

    /**
     * @var HfSocketIOPlatform
     */
    public $platform;

    /**
     * @var ChatlogFactory
     */
    public $factory;

    public function __construct(
        Shell $shell,
        HfSocketIOPlatform $platform,
        ? ChatlogSioRequest $request,
        ? UserInfo $user,
        ? InputInfo $input,
        ChatlogFactory $factory,
        BaseNamespace $controller,
        Socket $socket
    )
    {
        $this->platform = $platform;
        $this->input = $input;
        $this->user = $user;
        $this->shell = $shell;
        $this->factory = $factory;
        $this->socket = $socket;
        $this->request = $request;
        $this->emitter = $controller;
    }


    public function isInvalid(): ? string
    {
        return null;
    }

    public function adapt(string $adapterName, string $appId): Adapter
    {
        return new $adapterName($this, $appId);
    }

    public function fail(string $error): void
    {
        $errorInfo = new ErrorInfo([
            'errcode' => ErrorInfo::BAD_REQUEST,
            'errmsg' => $error,
        ]);
        $this->request->makeResponse($errorInfo)->emit($this->socket);
    }

    public function destroy(): void
    {
        unset(
            $this->platform,
            $this->shell,
            $this->input,
            $this->user,
            $this->socket,
            $this->request,
            $this->emitter
        );
    }


}