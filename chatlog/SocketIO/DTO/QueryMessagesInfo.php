<?php


namespace Commune\Chatlog\SocketIO\DTO;


use Commune\Support\Message\AbsMessage;
use Commune\Support\Utils\TypeUtils;

/**
 * @property-read string $scene
 * @property-read string $session
 * @property-read string|null $vernier
 * @property-read bool $forward
 * @property-read int $limit
 */
class QueryMessagesInfo extends AbsMessage
{
    public static function stub(): array
    {
        return [
            'scene' => '',
            'session' => '',
            'vernier' => null,
            'forward' => true,
            'limit' => 0,
        ];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        return TypeUtils::requireFields($data, ['scene', 'session', 'limit'])
            ?? parent::validate($data);
    }

    public static function relations(): array
    {
        return [];
    }

    public function isEmpty(): bool
    {
        return false;
    }


}