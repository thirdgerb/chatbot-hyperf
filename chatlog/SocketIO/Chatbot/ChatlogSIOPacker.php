<?php


namespace Commune\Chatlog\SocketIO\Chatbot;



use Commune\Blueprint\Platform\Adapter;
use Commune\Blueprint\Platform\Packer;
use Commune\Blueprint\Shell;
use Commune\Chatbot\Hyperf\Platforms\SocketIO\HfSocketIOPlatform;
use Commune\Chatlog\Database\ChatlogMessageRepo;
use Commune\Chatlog\SocketIO\Coms\RoomService;
use Commune\Chatlog\SocketIO\Protocal\ChatlogSioRequest;
use Commune\Chatlog\SocketIO\Protocal\ErrorInfo;
use Commune\Chatlog\SocketIO\Protocal\Input;
use Commune\Chatlog\SocketIO\Protocal\UserInfo;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\Socket;

class ChatlogSIOPacker implements Packer
{

    /**
     * @var Input
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
     * @var ChatlogMessageRepo
     */
    public $messageRepo;

    /**
     * @var BaseNamespace
     */
    public $emitter;

    /**
     * @var RoomService
     */
    public $roomService;

    /**
     * @var HfSocketIOPlatform
     */
    public $platform;

    /**
     * SIOPacker constructor.
     * @param Shell $shell
     * @param HfSocketIOPlatform $platform
     * @param BaseNamespace $emitter
     * @param Socket $socket
     * @param ChatlogSioRequest $request
     * @param ChatlogMessageRepo $messageRepo
     * @param Input $input
     * @param UserInfo $user
     */
    public function __construct(
        Shell $shell,
        HfSocketIOPlatform $platform,
        BaseNamespace $emitter,
        Socket $socket, 
        ChatlogSioRequest $request, 
        ChatlogMessageRepo $messageRepo,
        Input $input,
        UserInfo $user
    )
    {
        $this->platform = $platform;
        $this->input = $input;
        $this->user = $user;
        $this->shell = $shell;
        $this->socket = $socket;
        $this->request = $request;
        $this->messageRepo = $messageRepo;
        $this->emitter = $emitter;
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
            $this->input,
            $this->user,
            $this->shell,
            $this->socket,
            $this->request,
            $this->messageRepo,
            $this->emitter
        );
    }

    public function getEntry() : string
    {
    }


}