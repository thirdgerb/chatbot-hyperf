<?php

namespace Commune\Chatbot\Hyperf\Coms\Database;

use Carbon\Carbon;
use Commune\Message\Intercom\IInputMsg;
use Commune\Message\Intercom\IOutputMsg;
use Commune\Protocals\Intercom\InputMsg;
use Commune\Protocals\IntercomMsg;
use Commune\Support\Babel\Babel;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\Database\Query\Builder;
use Commune\Framework\Messenger\MessageDB\AbsCondition;

class MessageRepository
{
    const TABLE_NAME = 'messages';

    public static function countByCondition(
        Builder $builder,
        AbsCondition $condition
    ) : int
    {
        $builder = static::buildByCondition($builder, $condition);
        return $builder->count();
    }

    public static function fetchByCondition(
        Builder $builder,
        AbsCondition $condition
    ) : array
    {
        $builder = static::buildByCondition($builder, $condition);
        $collection = $builder->get();

        if ($collection->isEmpty()) {
            return [];
        }

        $messages = [];
        foreach ($collection as $message) {
            $messages[] = static::wrapMessage($message);
        }

        return $messages;
    }


    /**
     * @param IntercomMsg[] $messages
     * @return IntercomMsg[]
     */
    public static function sortByAsc(array $messages) : array
    {
        usort(
            $messages,
            function (IntercomMsg $first, IntercomMsg $second) {
                return $first->getCreatedAt() - $second->getCreatedAt();
            }
        );

        return $messages;
    }

    public static function buildByCondition(
        Builder $builder,
        AbsCondition $condition
    ) : Builder
    {

        if (isset($condition->id)) {
            $builder = $builder->where(
                'id',
                '=',
                $condition->id
            );
        }

        if (isset($condition->batchId)) {
            $builder = $builder->where(
                'batch_id',
                '=',
                $condition->batchId
            );
        }

        if (isset($condition->sessionId)) {
            $builder = $builder->where(
                'session_id',
                '=',
                $condition->batchId
            );
        }

        if (isset($condition->convoId)) {
            $builder = $builder->where(
                'convo_id',
                '=',
                $condition->convoId
            );
        }

        if (isset($condition->creatorId)) {
            $builder = $builder->where(
                'creator_id',
                '=',
                $condition->batchId
            );
        }

        if (isset($condition->deliverAfter)) {
            $builder = $builder->where(
                'deliver_at',
                '>',
                $condition->deliverAfter
            );
        }

        if (isset($condition->createdAfter)) {
            $builder = $builder->where(
                'created_at',
                '>',
                $condition->createdAfter
            );
        }

        if (isset($condition->idAfter)) {
            $builder = $builder->where(
                'message_id',
                '>',
                $condition->idAfter
            );
        }

        if (isset($condition->offset)) {
            $builder = $builder->offset($condition->offset);
        }

        if (isset($condition->limit)) {
            $builder = $builder->limit($condition->limit);
        }

        $builder->orderBy('message_id', 'desc');
        return $builder;
    }

    public static function fetchBatchMessages(
        Builder $builder,
        string $batchId
    ) : array
    {
        $data = $builder
            ->where(['batch_id' => $batchId])
            ->get();

        if ($data->isEmpty()) {
            return [];
        }

        $messages = [];
        foreach ($data as $message) {
            $messages[] = static::wrapMessage($message);
        }

        return $messages;
    }

    public static function wrapMessage(array $message) : IntercomMsg
    {
        $isInput = $message['is_input'];

        /**
         * @var Carbon $deliverAt
         * @var Carbon $createdAt
         */
        $deliverAt = $message['deliver_at'];
        $createdAt = $message['created_at'];

        $messageData = [
            'messageId' => $message['message_id'],
            'sessionId' => $message['session_id'],
            'batchId' => $message['batch_id'],
            'convoId' => $message['convo_id'],
            'creatorId' => $message['creator_id'],
            'creatorName' => $message['creator_name'],
            'message' => Babel::unserialize($message['message_data']),
            'createdAt' => $message['created_at'],
        ];

        $messageData['deliverAt'] = $deliverAt->timestamp;
        $messageData['createdAt'] = $createdAt->timestamp;

        return $isInput
            ? new IInputMsg($messageData)
            : new IOutputMsg($messageData);
    }

    public static function saveIntercomMsg(
        Builder $builder,
        string $traceId,
        string $fromApp,
        string $fromSession,
        IntercomMsg $message
    ) : bool
    {
        $data = [
            'is_input' => $message instanceof InputMsg,
            'trace_id' => $traceId,
            'from_app' => $fromApp,
            'from_session' => $fromSession,

            'batch_id' => $message->getBatchId(),
            'session_id' => $message->getSessionId(),
            'convo_id' => $message->getConvoId(),
            'message_id' => $message->getMessageId(),

            'creator_id' => $message->getCreatorId(),
            'creator_name' => $message->getCreatorName(),

            'deliver_at' => $message->getDeliverAt(),
            'created_at' => $message->getCreatedAt(),

            'message_data' => Babel::serialize($message->getMessage()),
        ];


        return $builder->updateOrInsert(
            ['message_id' => $message->getMessageId()],
            $data
        );

    }

    /**
     * 创建消息数据表.
     * @param Blueprint $table
     */
    public static function createTable(Blueprint $table) : void
    {
        $table->bigIncrements('id')
            ->comment('消息表的子增ID');

        SchemaHelper::uuidField($table, 'message_id')
            ->comment('消息ID. ');

        SchemaHelper::idField($table, 'traceId_id')
            ->comment('服务化调用的追踪ID');

        SchemaHelper::uuidField($table, 'batch_id')
            ->comment('消息的批次, 通常由输入消息决定');

        SchemaHelper::idField($table, 'session_id')
            ->comment('消息相对于 Ghost 的 session id');

        SchemaHelper::idField($table, 'from_session')
            ->comment('消息起源的 sessionId. ');

        SchemaHelper::idField($table, 'convo_id')
            ->comment('消息所属的多轮对话 ID');

        SchemaHelper::idField($table, 'creator_id')
            ->comment('消息创建者的 ID');

        $table->string('creator_name', 30)
            ->comment('消息创建者在所在平台的名称. ');

        $table->boolean('is_input')
            ->comment('是否是输入消息')
            ->default(false);

        $table->string('from_app', 50)
            ->comment('输入消息从哪个设备起源');


        $table->text('message')
            ->comment("消息体序列化后的存储,需要反序列化");

        $table->timestamp('deliver_at')
            ->comment('消息发送时间.');

        $table->timestamps();

        $table->unique('message_id', 'uqx_message_id');
        $table->index('batch_id', 'idx_batch_id');
        $table->index(['session_id', 'convo_id'], 'idx_session_convo');
    }

}