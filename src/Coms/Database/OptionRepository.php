<?php


/**
 * Class OptionRepository
 * @package Commune\Chatbot\Hyperf\Coms\Database
 */

namespace Commune\Chatbot\Hyperf\Coms\Database;


use Hyperf\Database\Schema\Blueprint;

class OptionRepository
{
    const TABLE_NAME = 'options';

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

        $table->timestamps();

        $table->unique('uuid', 'uqx_uuid');
        $table->index(['category_name', 'option_id'], 'idx_cate_opt');
    }

}