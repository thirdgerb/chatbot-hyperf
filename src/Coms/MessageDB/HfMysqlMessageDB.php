<?php


/**
 * Class HfMysqlMessageDB
 * @package Commune\Chatbot\Hyperf\Coms\MessageDB
 */

namespace Commune\Chatbot\Hyperf\Coms\MessageDB;


use Carbon\Carbon;
use Commune\Blueprint\Exceptions\IO\LoadDataException;
use Commune\Blueprint\Exceptions\IO\SaveDataException;
use Commune\Chatbot\Hyperf\Coms\Database\MessageRepository;
use Commune\Contracts\Cache;
use Commune\Contracts\Messenger\Condition;
use Commune\Framework\Messenger\MessageDB\AbsMessageDB;
use Commune\Protocals\IntercomMsg;
use Commune\Support\Babel\Babel;
use Commune\Support\Utils\ArrayUtils;
use Hyperf\Database\Query\Builder;
use Hyperf\DbConnection\Db;
use Psr\Log\LoggerInterface;
use Swoole\Coroutine;

class HfMysqlMessageDB extends AbsMessageDB
{
    /**
     * @var string
     */
    protected $connection;

    /**
     * @var string
     */
    protected $tableName;


    public function __construct(
        Cache $cache,
        LoggerInterface $logger,
        string $connection,
        string $tableName,
        int $cacheTtl
    )
    {
        $this->connection = $connection;
        $this->tableName = $tableName;
        $this->cacheTtl = $cacheTtl;
        parent::__construct($cache, $logger, $cacheTtl);
    }


    public function saveBatchMessages(
        string $traceId,
        string $fromApp,
        string $fromSession,
        string $batchId,
        IntercomMsg ...$outputs
    ): void
    {
        $messages = [];

        foreach ($outputs as $output) {

            $message = $output->getMessage();

            $data = [
                'message_id' => $output->getMessageId(),
                'trace_id' => $traceId,
                'batch_id' => $output->getBatchId(),
                'session_id' => $output->getSessionId(),
                'from_session' => $fromSession,
                'from_app' => $fromApp,
                'convo_id' => $output->getConvoId(),
                'creator_id' => $output->getCreatorId(),
                'creator_name' => $output->getCreatorName(),
                'message' => Babel::serialize($output->getMessage()),

                'deliver_at' => Carbon::createFromTimestamp($output->getDeliverAt())->toDateTimeString(),
                'created_at' => Carbon::createFromTimestamp($output->getCreatedAt())->toDateTimeString(),
            ];

            $messages[] = $data;
        }

        try {

            Coroutine::create(function($messages) {
                $builder = $this->newBuilder();
                $builder->insert($messages);
            }, $messages);

        } catch (\Throwable $e) {
            $this->logger->error($e);

            foreach ($messages as $data) {
                $this->logger->notice('unsaved message : '. json_encode($data));
            }

            throw new SaveDataException(
                __METHOD__ . " save data failed",
                $e
            );

        }
    }

    public function newBuilder() : Builder
    {
        return Db::connection($this->connection)->table($this->tableName);
    }

    public function loadBatchMessages(string $batchId): array
    {
        try {
            $builder = $this->newBuilder();
            $messages = MessageRepository::fetchBatchMessages(
                $builder,
                $batchId
            );

            return $messages;

        } catch (\Throwable $e) {
            $this->logger->error($e);

            throw new LoadDataException(
                __METHOD__ . " load batch messages of $batchId failed",
                $e
            );

        }
    }

    public function where(): Condition
    {
        return new HfMysqlCondition($this, $this->logger);
    }

    public function find(string $messageId): ? IntercomMsg
    {
        $build = $this->newBuilder();

        try {

            $data = $build
                ->where('message_id', '=', $messageId)
                ->first();

            if (empty($data)) {
                return null;
            }

            $data = ArrayUtils::recursiveToArray($data);
            return MessageRepository::wrapMessage($data);

        } catch (\Throwable $e) {

            $this->logger->error($e);

            throw new LoadDataException(
                __METHOD__ . " load batch messages of $messageId failed",
                $e
            );
        }
    }


}