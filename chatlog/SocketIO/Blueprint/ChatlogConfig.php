<?php


namespace Commune\Chatlog\SocketIO\Blueprint;

use Commune\Support\Option\Option;

/**
 * @property-read string $appName
 * @property-read string $jwtSigner
 * @property-read string $jwtSecret
 * @property-read int $jwtExpire   seconds
 * @property-read string[] $protocals
 *
 * @property-read string $dbConnection
 *
 * @property-read string $userHashSigner
 * @property-read string $userHashSalt
 */
interface ChatlogConfig extends Option
{
}