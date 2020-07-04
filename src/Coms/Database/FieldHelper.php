<?php


namespace Commune\Chatbot\Hyperf\Coms\Database;

use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Schema\ColumnDefinition;


class FieldHelper
{
    public static $uuidLength = 50;

    public static $protocalIdLength = 200;

    public static function uuidField(Blueprint $table, string $name) : ColumnDefinition
    {
        return $table->string($name, self::$uuidLength);
    }




}