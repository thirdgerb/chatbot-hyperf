<?php


namespace Commune\Chatlog\Database;

use Carbon\Carbon;
use Commune\Blueprint\Exceptions\Logic\InvalidArgumentException;
use Commune\Chatlog\SocketIO\Protocal\MessageBatch;
use Commune\Support\Babel\Babel;
use Commune\Support\Swoole\SwooleUtils;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Commune\Chatlog\ChatlogConfig;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Utils\Collection;
use Swoole\Coroutine;

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
        $table->increments('id');
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
        $messages = array_filter($messages, function(MessageBatch $batch) {
            return $batch->shouldSave();
        });

        if (empty($messages)) {
            return true;
        }

        $data = array_map(function(MessageBatch $batch) use ($shell){
            return [
                'batch_id' => $batch->batchId,
                'session' => $batch->session,
                'shell' => $shell,
                'data' => Babel::serialize($batch),
                'created_at' => Carbon::createFromTimestamp($batch->createdAt),
            ];
        }, $messages);

        if (SwooleUtils::isInCoroutine()) {

            // 使用协程保存.
            Coroutine::create(function(Builder $builder, $data) {
                $builder->insert($data);
            }, $this->newBuilder(), $data);

            return true;
        }
        return $this->newBuilder()->insert($data);
    }


    /**
     * 以时间为游标来获取历史消息.
     *
     * 总体而言有三种策略:
     * 1. 最新的 N 条消息
     * 2. => 游标最老的 N 条消息
     * 3. <= 游标最新的 N 条消息.
     *
     * 游标理论上应该精确到毫秒, 或者用一个有序的ID 作为游标.
     * 但目前的业务没必要做这么精细, 所以算了.
     *
     * @param string $session
     * @param string $batchId
     * @param int $limit
     * @param bool $forward
     * @return MessageBatch[]
     */
    public function fetchMessagesByBatchId(
        string $session,
        int $limit,
        string $batchId = null,
        bool $forward = false
    ) : array
    {
        if ($limit < 1 || $limit > 100) {
            throw new InvalidArgumentException("Chatlog Demo limit should between 1 to 100");
        }


        if (isset($batchId)) {
            $vernier = $this->findVernier($batchId);
            // 如果游标不存在.
            if (empty($vernier)) {
                return [];
            }

            return $forward
                ? $this->fetchNearestMessagesAfterVernier($session, $vernier, $limit)
                : $this->fetchNearestMessagesBeforeVernier($session, $vernier, $limit);
        }

        return $forward
            ? $this->fetchNewestMessages($session, $limit)
            : $this->fetchOldestMessages($session, $limit);
    }



    public function fetchOldestMessages(
        string $session,
        int $limit
    ) : array
    {
        $collection = $this->newBuilder()
            ->where('session', '=', $session)
            ->limit($limit)
            ->orderBy('id', 'asc')
            ->get(['data']);

        return $this->unpackCollection($collection);
    }

    public function fetchNewestMessages(
        string $session,
        int $limit
    ) : array
    {
        $collection = $this->newBuilder()
            ->where('session', '=', $session)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get(['data']);

        return $this->unpackCollection($collection);
    }

    public function fetchNearestMessagesAfterVernier(
        string $session,
        int $vernier,
        int $limit
    ) : array
    {
        $collection = $this->newBuilder()
            ->where('session', '=', $session)
            ->where('id', '>', $vernier)
            ->limit($limit)
            ->orderBy('id', 'asc')
            ->get(['data']);

        return $this->unpackCollection($collection);
    }


    public function fetchNearestMessagesBeforeVernier(
        string $session,
        int $vernier,
        int $limit
    ) : array
    {
        $collection = $this->newBuilder()
            ->where('session', '=', $session)
            ->where('id', '<', $vernier)
            ->limit($limit)
            ->orderBy('id', 'desc')
            ->get(['data']);

        return $this->unpackCollection($collection);
    }

    protected function unpackCollection(Collection $collection) : array
    {
        return $collection
            ->map(function($obj) {
                return Babel::unserialize($obj->data);
            })->filter(function($obj) {
                return $obj instanceof MessageBatch;
            })->sort(function(MessageBatch $a, MessageBatch $b) {
                return $a->createdAt - $b->createdAt;
            })->all();
    }


    public function findVernier(string $batchId) : ? string
    {
        $data = $this->newBuilder()
            ->where('batch_id', '=', $batchId)
            ->first(['id']);

        return $data ? $data->id : null;
    }
}