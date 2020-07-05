<?php


namespace Commune\Chatbot\Hyperf\Coms\Database;

use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;


class SchemaHelper
{
    public static function uuidField(Blueprint $table, string $name) : ColumnDefinition
    {
        return $table->string($name);
    }

    public static function idField(Blueprint $table, string $name) : ColumnDefinition
    {
        return $table->string($name);
    }

    public static function timestamps(Blueprint $table) : void
    {
        $table->timestamp('created_at')->default(CURRENT_TIMESTAMP);
    }



}