<?php


namespace Commune\Chatlog\SocketIO\DTO;


use Commune\Support\Message\AbsMessage;

/**
 * @property-read string $scene
 */
class QuerySceneInfo extends AbsMessage
{
    public static function stub(): array
    {
        return [
            'scene' => '',
        ];
    }

    public static function relations(): array
    {
        return [];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {
        if (empty($data['scene'])) {
            return 'scene is required';
        }
        return parent::validate($data);
    }

    public function isEmpty(): bool
    {
        return false;
    }


}