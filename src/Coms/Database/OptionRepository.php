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


    public static function uuidExists(
        Builder $builder,
        string $uuid
    ) : bool
    {
        return $builder->where('uuid', '=', $uuid)->exists();
    }

    /**
     * @param Builder $builder
     * @param string $uuid
     * @param array $columns
     * @return null|\stdClass
     */
    public static function findOptionByUuid(
        Builder $builder,
        string $uuid,
        array $columns = ['*']
    ) : ? \stdClass
    {
        return $builder->where('uuid', '=', $uuid)->first($columns);
    }


    /**
     * @param Builder $builder
     * @param string $cateName
     * @param int $offset
     * @param int $limit
     * @param array $columns
     * @param int|null $vernier
     * @param string|null $search
     * @return \stdClass[]
     */
    public static function paginateCategory(
        Builder $builder,
        string $cateName,
        int $offset,
        int $limit,
        array $columns = ['*'],
        int $vernier = null,
        string $search = null
    ) : array
    {
        $builder = $builder->where('category_name', '=', $cateName);
        if (isset($vernier)) {
            $builder = $builder->where('id', '>', $vernier);
        }

        if (isset($search)) {
            $builder = $builder->where(function(Builder $builder) use ($search){
                return $builder
                    ->where('option_id', 'like', "%$search%")
                    ->orWhere('title', 'like', "%$search%");
            });
        }

        $builder = $builder->orderBy('id', 'asc')
            ->offset($offset)
            ->limit($limit);

        $collection = $builder->get($columns);

        return $collection->all();
    }

    public static function countCategory(
        Builder $builder,
        string $cateName,
        string $search = null
    ) : int
    {
        $builder = $builder->where('category_name', '=', $cateName);
        if (isset($search)) {
            $builder = $builder->where(function(Builder $builder) use ($search) {
                return $builder
                    ->where('option_id', 'like', "%$search%")
                    ->orWhere('title', 'like', "%$search%");
            });
        }

        return $builder->count();
    }

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