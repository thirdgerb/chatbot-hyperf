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

/**
 * 修改 Hyperf SocketIO 的 Server, 主要是加进去 Host.
 */
class HfSocketIO extends SocketIO
{
    /**
     * @var Host
     */
    protected $host;

    /**
     * @var LoggerInterface
     */
    protected $stdoutLogger;

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

        // 替换日志为 LoggerInterface
        $this->stdoutLogger = $host
            ->getProcContainer()
            ->make(LoggerInterface::class);
    }

}