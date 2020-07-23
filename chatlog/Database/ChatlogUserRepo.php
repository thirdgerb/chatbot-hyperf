<?php


namespace Commune\Chatlog\Database;


use Carbon\Carbon;
use Commune\Blueprint\Framework\Auth\Supervise;
use Commune\Chatlog\SocketIO\Blueprint\ChatlogConfig;
use Commune\Container\ContainerContract;
use Hyperf\Database\Query\Builder;
use Hyperf\Database\Schema\Blueprint;
use Hyperf\DbConnection\Db;
use Lcobucci\JWT\Signer;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key;

class ChatlogUserRepo
{
    const TABLE_NAME = 'chatlog_users';

    /**
     * @var ChatlogConfig
     */
    protected $config;

    /**
     * @var ContainerContract
     */
    protected $container;

    public static function createTable(Blueprint $table) : void
    {
        $table->increments('id');
        $table->string('uuid')->comment('全系统的唯一ID')->default('');
        $table->string('user_id');
        $table->string('name');
        $table->string('password_hash');
        $table->tinyInteger('level');
        $table->timestamp('createdAt');

        $table->unique('user_id', 'unq_user');
        $table->unique('name', 'unq_name');
    }

    public function newBuilder() : Builder
    {
        return Db::connection($this->config->dbConnection)->table(self::TABLE_NAME);
    }

    public function userNameExists(string $name) : bool
    {
        return $this->newBuilder()
            ->where('name', '=', $name)
            ->exists();
    }

    public function register(
        string $userId,
        string $name,
        string $password,
        int $level = Supervise::GUEST,
        string $uuid = null
    ) : bool
    {
        if (empty($userId) || empty($name)) {
            throw new \InvalidArgumentException("user id or name should not empty");
        }

        if ($level > 0 && empty($password)) {
            throw new \InvalidArgumentException('only guest user could have empty password');
        }

        $data = [
            'user_id' => $userId,
            'name' => $name,
            'password_hash' => $this->hashPassword($password),
            'level' => $level,
            'uuid' => $uuid ?? '',
            'createdAt' => new Carbon(),
        ];

        return $this->newBuilder()->insert([$data]);
    }

    public function hashPassword(string $password) : string
    {
        if (empty($password)) {
            return '';
        }

        /**
         * @var Signer $signer
         */
        $signer = $this->container->make($this->config->userHashSigner);
        $key = new Key($this->config->userHashSalt);
        $hash = $signer->sign($password, $key);
        return $hash;
    }

    public function verifyUser(string $name, string $password) : \stdClass
    {
        $hash = $this->hashPassword($password);

        return $this->newBuilder()
            ->where('name', '=', $name)
            ->where('password_hash', '=', $hash)
            ->first();
    }


}