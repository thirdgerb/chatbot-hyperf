<?php


namespace Commune\Chatlog\Database;


use Hyperf\Database\Schema\Blueprint;

class ChatlogMessageRepo
{
    const TABLE_NAME = 'chatlog_messages';

    public static function createTable(Blueprint $table) : void
    {
        $table->bigIncrements('id');
        $table->string('batchId');
        $table->string('session');
        $table->string('shell');
        $table->string('creatorId');
        $table->text('data');
        $table->integer('createdAt');

        $table->unique('batch_id', 'unq_batch_id');
        $table->index(['session', 'createdAt'], 'idx_session_created');
    }
}