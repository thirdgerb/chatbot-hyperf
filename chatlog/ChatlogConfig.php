<?php


namespace Commune\Chatlog;

use Commune\Chatlog\SocketIO\Coms\RoomOption;
use Commune\Support\Option\Option;

/**
 * @property-read string $appName               应用的名称.
 * @property-read bool $debug
 *
 * ## 机器人相关.
 *
 * @property-read string $adapterName
 *
 * ## jwt 相关
 * @property-read string $jwtSigner             Jwt Signer 类名
 * @property-read string $jwtSecret             Jwt 密钥
 * @property-read int $jwtExpire   seconds      Jwt 过期时间.
 *
 * ## socket io 事件协议.
 *
 *
 * @property-read string[] $protocals           协议与 handler 列表. $eventName => $eventHandlerName
 *
 * ## io
 *
 * @property-read string $dbConnection
 *
 * ## user
 * @property-read string $userHashSigner        用户密码加密的 signer
 * @property-read string $userHashSalt          用户密码加密的盐
 *
 *
 * ## 房间相关.
 * @property-read RoomOption[] $rooms           系统定义的房间.
 * @property-read string $supervisorScene       超管所在的房间场景.
 */
interface ChatlogConfig extends Option
{

    public function getAppId() : string;
}