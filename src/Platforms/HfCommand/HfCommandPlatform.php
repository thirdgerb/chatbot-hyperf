<?php


/**
 * Class HfCommandPlatform
 * @package Commune\Chatbot\Hyperf\Platforms\HfCommand
 */

namespace Commune\Chatbot\Hyperf\Platforms\HfCommand;


use Commune\Blueprint\Ghost;
use Commune\Blueprint\Kernel\Protocals\AppRequest;
use Commune\Blueprint\Platform;
use Commune\Blueprint\Shell;
use Commune\Chatbot\Hyperf\Command\StartAppCommand;
use Commune\Platform\AbsPlatform;
use Hyperf\Command\Command;
use Swoole\Coroutine;

class HfCommandPlatform extends AbsPlatform
{
    /**
     * @var Shell
     */
    protected $shell;

    /**
     * @var Ghost
     */
    protected $ghost;

    /**
     * @var Command
     */
    protected $command;

    public function getAppId(): string
    {
        return $this->shell->getId();
    }

    public function serve(): void
    {

        $quit = false;

        while (!$quit) {


        }
    }

    public function sleep(float $seconds): void
    {
        usleep($seconds * 1000000);
    }

    public function shutdown(): void
    {
        exit(0);
    }

    protected function handleRequest(
        Platform\Adapter $adapter,
        AppRequest $request,
        string $interface = null
    ): void
    {
        // TODO: Implement handleRequest() method.
    }


}