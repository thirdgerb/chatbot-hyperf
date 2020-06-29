<?php


/**
 * Class HfRuntimeDriver
 * @package Commune\Chatbot\Hyperf\Coms\Runtime
 */

namespace Commune\Chatbot\Hyperf\Coms\Runtime;


use Commune\Blueprint\Ghost\Memory\Memory;
use Commune\Blueprint\Ghost\Runtime\Process;
use Commune\Framework\RuntimeDriver\ARuntimeDriver;

class HfRuntimeDriver extends ARuntimeDriver
{
    protected function getProcessKey(string $cloneId, string $convoId): string
    {
        // TODO: Implement getProcessKey() method.
    }

    protected function doCacheProcess(string $key, Process $process, int $expire): bool
    {
        // TODO: Implement doCacheProcess() method.
    }

    protected function doFetchProcess(string $key): ? Process
    {
        // TODO: Implement doFetchProcess() method.
    }

    protected function getSessionMemoriesCacheKey(string $cloneId, string $convoId): string
    {
        // TODO: Implement getSessionMemoriesCacheKey() method.
    }

    protected function doCacheSessionMemories(string $key, array $map, int $expire): bool
    {
        // TODO: Implement doCacheSessionMemories() method.
    }

    protected function doFetchSessionMemory(string $key, string $memoryId): ? string
    {
        // TODO: Implement doFetchSessionMemory() method.
    }

    public function saveLongTermMemories(
        string $clonerId,
        array $memories
    ): bool
    {
        // TODO: Implement saveLongTermMemories() method.
    }

    public function findLongTermMemories(string $clonerId, string $memoryId): ? Memory
    {
        // TODO: Implement findLongTermMemories() method.
    }


}