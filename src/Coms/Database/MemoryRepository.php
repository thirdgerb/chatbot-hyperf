<?php


/**
 * Class MemoryTable
 * @package Commune\Chatbot\Hyperf\Coms\Database
 */

namespace Commune\Chatbot\Hyperf\Coms\Database;

use Commune\Blueprint\Ghost\Memory\Memory;
use Commune\Support\Babel\Babel;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Query\Builder;

class MemoryRepository
{
    const TABLE_NAME = 'memories';

    public static function createTable(Blueprint $table) : void
    {
        $table->increments('id');

        FieldHelper::uuidField($table, 'memory_id')
            ->comment('记忆体ID');

        $table->binary('memory_data')->comment('记忆体的内容');

        $table->timestamps();

        $table->unique('memory_id');
    }

    /**
     * @param Builder $builder
     * @param Memory[] $memories
     */
    public static function saveMemories(
        Builder $builder,
        array $memories
    ) : void
    {
        foreach ($memories as $memory) {
            $memoryId = $memory->getId();
            $memoryData = Babel::serialize($memory);

            $builder->updateOrInsert(
                ['memory_id' => $memoryId],
                ['memory_data' => $memoryData]
            );
        }
    }

    public static function findMemory(
        Builder $builder,
        string $cloneId,
        string $memoryId
    ) : ? Memory
    {
        $data = $builder->where('memory_id', '=', $memoryId)->first(['memory_data']);

        return empty($data)
            ? null
            : Babel::unserialize($data['memory_data']);
    }


}