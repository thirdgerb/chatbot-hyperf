<?php


/**
 * Class SymfonyStyleConsole
 * @package Commune\Chatbot\Hyperf\Coms\Console
 */

namespace Commune\Chatbot\Hyperf\Coms\Console;

use Psr\Log\LogLevel;
use Commune\Framework\Log\IConsoleLogger;
use Symfony\Component\Console\Style\SymfonyStyle;

class SymfonyStyleConsole extends IConsoleLogger
{

    /**
     * @var SymfonyStyle
     */
    protected $style;

    public function __construct(
        SymfonyStyle $style,
        bool $showLevel = true,
        string $startLevel = LogLevel::DEBUG
    )
    {
        $this->style = $style;
        parent::__construct($showLevel, $startLevel);
    }

    protected function write(string $string): void
    {
        $this->style->write($string);
    }

}