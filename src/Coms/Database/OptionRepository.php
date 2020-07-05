<?php


/**
 * Class OptionRepository
 * @package Commune\Chatbot\Hyperf\Coms\Database
 */

namespace Commune\Chatbot\Hyperf\Coms\Database;


use Commune\Support\Option\Option;
use Hyperf\Database\Query\Builder;
use Hyperf\Database\Schema\Blueprint;

class OptionRepository
{
    const TABLE_NAME = 'options';


    public static function deleteByUuid(
        Builder $builder,
        string ...$uuids
    ) : int
    {
        return $builder->whereIn('uuid', $uuids)->delete();
    }

    public static function saveOption(
        Builder $builder,
        Option $option,
        string $categoryName,
        string $uuid,
        string $serialized
    ) : bool
    {
        $now = time();
        return $builder->updateOrInsert(
            ['uuid' => $uuid],
            [
                'uuid' => $uuid,
                'option_id' => $option->getId(),
                'title' => $option->getTitle(),
                'desc' => $option->getDescription(),
                'category_name' => $categoryName,
                'data' => $serialized,
            ]
        );
    }

    public static function createTable(Blueprint $table) : void
    {
        $table->increments('id');

        $table->char('uuid', 40);

        // category name
        $table->string('category_name', 100);

        $table->string('option_id');

        $table->string('title');
        $table->string('desc');

        $table->text('data');

        $table->unique('uuid', 'uqx_uuid');
        $table->index(['category_name', 'option_id'], 'idx_cate_opt');
    }

}