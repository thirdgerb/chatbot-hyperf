<?php


namespace Commune\Chatbot\Hyperf\Hacks\Websocket;


use Throwable;
use Commune\Blueprint\Host;
use Commune\Contracts\Log\ExceptionReporter;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Hyperf\WebSocketServer\Exception\Handler\WebSocketExceptionHandler;


/**
 * 用自定义的 ExceptionHandler 代替 Hyperf 默认的.
 */
class HfWebsocketExceptionHandler extends WebSocketExceptionHandler
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    /**
     * @var Host
     */
    protected $host;

    public function __construct(
        Host $host,
        StdoutLoggerInterface $stdoutLogger,
        FormatterInterface $formatter
    )
    {
        parent::__construct($stdoutLogger, $formatter);
        $this->host = $host;
        $this->logger = $host
            ->getProcContainer()
            ->make(LoggerInterface::class);
        $this->formatter = $formatter;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        if ($throwable instanceof \Error) {
            /**
             * @var ExceptionReporter $reporter
             */
            $reporter = $this->host->getProcContainer()->make(ExceptionReporter::class);
            $reporter->report($throwable);
        }

        $response = parent::handle($throwable, $response);
        $this->stopPropagation();
        return $response;
    }
}