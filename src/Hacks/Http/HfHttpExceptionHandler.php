<?php


namespace Commune\Chatbot\Hyperf\Hacks\Http;

use Commune\Blueprint\Host;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\ExceptionHandler\Formatter\FormatterInterface;
use Hyperf\HttpServer\Exception\Handler\HttpExceptionHandler;
use Psr\Log\LoggerInterface;

/**
 * 调整默认的异常管理.
 */
class HfHttpExceptionHandler extends HttpExceptionHandler
{

    public function __construct(
        Host $host,
        StdoutLoggerInterface $logger,
        FormatterInterface $formatter
    )
    {
        parent::__construct($logger, $formatter);
        // 将默认的日志替换成 loggerInterface.
        // hyperf 的默认是记录到 console, 与 commune 的策略不一致.
        $this->logger = $host->getProcContainer()->make(LoggerInterface::class);
    }

}