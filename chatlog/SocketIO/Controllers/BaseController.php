<?php

namespace Commune\Chatlog\SocketIO\Controllers;


use Commune\Blueprint\Host;
use Commune\Contracts\Log\ConsoleLogger;
use Hyperf\SocketIOServer\BaseNamespace;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Hyperf\SocketIOServer\Socket;
use Hyperf\WebSocketServer\Sender;

class BaseController extends BaseNamespace
{
    /**
     * @var Host
     */
    protected $host;

    /**
     * @var ConsoleLogger
     */
    protected $console;

    public function __construct(
        Host $host,
        Sender $sender,
        SidProviderInterface $sidProvider
    ) {
        $this->host = $host;
        $this->console = $host->getConsoleLogger();

        parent::__construct($sender,$sidProvider);
        $this->on('message', [$this, 'onMessage']);
    }

    public function onMessage(Socket $socket, $data)
    {
        $this->console->info('message : ' . $data);
    }

}