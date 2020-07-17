<?php


/**
 * Class StdoutConsoleLogger
 * @package Commune\Chatbot\Hyperf\Coms\Console
 */

namespace Commune\Chatbot\Hyperf\Coms\Console;


use Commune\Blueprint\CommuneEnv;
use Commune\Framework\Log\IConsoleLogger;
use Hyperf\Framework\Logger\StdoutLogger;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Hyperf\Contract\StdoutLoggerInterface;
use Commune\Contracts\Log\ConsoleLogger;


class StdoutConsoleLogger implements ConsoleLogger
{
    use LoggerTrait;

    /**
     * @var StdoutLoggerInterface
     */
    protected $logger;

    /**
     * StdConsoleLogger constructor.
     * @param StdoutLoggerInterface $logger
     */
    public function __construct(StdoutLoggerInterface $logger)
    {
        $this->logger = $logger;
    }


    public function log($level, $message, array $context = array())
    {
        $message = IConsoleLogger::wrapMessage($level, $message);
        $this->logger->log($level, strval($message), $context);
    }
}



