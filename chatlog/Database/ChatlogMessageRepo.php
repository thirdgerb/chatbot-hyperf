<?php


namespace Commune\Chatlog\Database;

use Carbon\Carbon;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Support\Babel\Babel;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Commune\Chatlog\SocketIO\Blueprint\ChatlogConfig;
use Hyperf\Database\Schema\Blueprint;

class ChatlogMessageRepo
{
    const TABLE_NAME = 'chatlog_messages';

    /**
     * @var ChatlogConfig
     */
    protected $config;

    /**
     * ChatlogMessageRepo constructor.
     * @param ChatlogConfig $config
     */
    public function __construct(ChatlogConfig $config)
    {
        $this->config = $config;
    }


    public static function createTable(Blueprint $table) : void
    {
        $table->bigIncrements('id');
        $table->string('batch_id');
        $table->string('session');
        $table->string('shell');
        $table->text('data');
        $table->timestamp('created_at');

        $table->unique('batch_id', 'unq_batch_id');
        $table->index(['session', 'batch_id'], 'idx_session_batch');
    }

    public function newBuilder() : Builder
    {
        return Db::connection($this->config->dbConnection)->table(self::TABLE_NAME);
    }


    public function saveBatch(string $shell, MessageBatch ...$messages) : bool
    {
        $data = array_map(function(MessageBatch $batch) use ($shell){
            return [
                'batch_id' => $batch->batchId,
                'session' => $batch->session,
                'shell' => $shell,
                'data' => Babel::serialize($batch),
                'created_at' => Carbon::createFromTimestamp($batch->createdAt),
            ];
        }, $messages);

        return $this->newBuilder()->insert($data);
    }

}