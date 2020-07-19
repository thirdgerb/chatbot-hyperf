<?php


namespace Commune\Chatbot\Hyperf\Hacks\Websocket;


use Commune\Blueprint\Host;
use Hyperf\ExceptionHandler\ExceptionHandler;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpMessage\Exception\NotFoundHttpException;
use Hyperf\HttpMessage\Stream\SwooleStream;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;
use Hyperf\HttpMessage\Exception\HttpException;


class HfWebsocketExceptionHandler extends ExceptionHandler
{
    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * @var FormatterInterface
     */
    protected $formatter;

    public function __construct(Host $host, FormatterInterface $formatter)
    {
        $this->logger = $host->getProcContainer()->make(LoggerInterface::class);
        $this->formatter = $formatter;
    }

    public function handle(Throwable $throwable, ResponseInterface $response)
    {
        $stream = new SwooleStream((string) $throwable->getMessage());

        if ($throwable instanceof HttpException) {
            $code = $throwable->getStatusCode();
            $this->logger->warning(
                get_class($throwable)
                . ": code $code, message " . $throwable->getMessage()
            );

        } else {
            $code = 200;
            $this->logger->warning($this->formatter->format($throwable));
        }

        return $response->withStatus($code)->withBody($stream);
    }

    public function isValid(Throwable $throwable): bool
    {
        return true;
    }
}