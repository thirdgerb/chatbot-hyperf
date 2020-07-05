<?php


/**
 * Class HfCommandPacker
 * @package Commune\Chatbot\Hyperf\Platforms\HfCommand
 */

namespace Commune\Chatbot\Hyperf\Platforms\HfCommand;


use Commune\Blueprint\Platform\Adapter;
use Commune\Blueprint\Platform\Packer;
use Hyperf\Command\Command;

class HfCommandPacker implements Packer
{

    /**
     * @var Command
     */
    public $command;

    /**
     * @var HfCommandPlatform
     */
    public $platform;


    public function isInvalid(): ? string
    {
        return null;
    }

    public function adapt(string $adapterName, string $appId): Adapter
    {
        return new $adapterName($this, $appId);
    }

    public function fail(string $error): void
    {
        $this->command->error($error);
    }

    public function destroy(): void
    {
        unset(
            $this->command,
            $this->platform
        );
    }


}