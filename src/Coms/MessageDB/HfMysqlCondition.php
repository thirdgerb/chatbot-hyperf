<?php


/**
 * Class HfMysqlCondition
 * @package Commune\Chatbot\Hyperf\Coms\MessageDB
 */

namespace Commune\Chatbot\Hyperf\Coms\MessageDB;

use Psr\Log\LoggerInterface;
use Commune\Blueprint\Exceptions\IO\LoadDataException;
use Commune\Framework\Messenger\MessageDB\AbsCondition;
use Commune\Chatbot\Hyperf\Coms\Database\MessageRepository;

class HfMysqlCondition extends AbsCondition
{

    /**
     * @var HfMysqlMessageDB
     */
    protected $db;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(HfMysqlMessageDB $db, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->db = $db;
    }

    public function get(): array
    {
        try {
            $builder = $this->db->newBuilder();
            return MessageRepository::fetchByCondition(
                $builder,
                $this
            );

        } catch (\Throwable $e) {
            $this->logger->error($e);
            throw new LoadDataException(
                __METHOD__ . " failed",
                $e
            );
        }
    }


    public function count(): int
    {
        try {
            $builder = $this->db->newBuilder();
            $builder = MessageRepository::buildByCondition(
                $builder,
                $this
            );

            return $builder->count();

        } catch (\Throwable $e) {

            $this->logger->error($e);
            throw new LoadDataException(
                __METHOD__ . " failed",
                $e
            );

        }
    }


}