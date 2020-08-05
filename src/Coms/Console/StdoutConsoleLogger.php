<?php


/**
 * Class StdoutConsoleLogger
 * @package Commune\Chatbot\Hyperf\Coms\Console
 */

namespace Commune\Chatbot\Hyperf\Coms\Console;


use Commune\Framework\Log\IConsoleLogger;
use Psr\Log\LoggerTrait;
use Hyperf\Contract\StdoutLoggerInterface;
use Commune\Contracts\Log\ConsoleLogger;
use Symfony\Component\Console\Output\ConsoleOutput;


class StdoutConsoleLogger implements ConsoleLogger
{
    use LoggerTrait;

    /**
     * @var ConsoleOutput
     */
    protected $output;

    public function __construct()
    {
        $this->output = new ConsoleOutput();
    }


    public function log($level, $message, array $context = array())
    {
        $json = empty($context)
            ? ''
            : json_encode(
                $context,
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
            );
        $message = strval($message). ' ' . $json;
        $message = IConsoleLogger::wrapMessage($level, $message);
        $this->output->writeln("[$level]" . $message);
    }
}



