<?php


namespace Commune\Chatbot\Hyperf\Hacks\SockeIO;


use Commune\Blueprint\Host;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\SocketIOServer\Parser\Decoder;
use Hyperf\SocketIOServer\Parser\Encoder;
use Hyperf\SocketIOServer\SidProvider\SidProviderInterface;
use Hyperf\SocketIOServer\SocketIO;
use Hyperf\WebSocketServer\Sender;
use Psr\Log\LoggerInterface;

class HfSocketIO extends SocketIO
{
    /**
     * @var Host
     */
    protected $host;

    public function __construct(
        Host $host,
        StdoutLoggerInterface $stdoutLogger,
        Sender $sender,
        Decoder $decoder,
        Encoder $encoder,
        SidProviderInterface $sidProvider
    )
    {
        $this->host = $host;
        parent::__construct($stdoutLogger, $sender, $decoder, $encoder, $sidProvider);

        // 替换默认的日志. 不要打印在 console里.
        // todo
        // $this->stdoutLogger = $this->host->getProcContainer()->get(LoggerInterface::class);
    }

}