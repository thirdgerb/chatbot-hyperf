<?php


namespace Commune\Chatlog\SocketIO\Protocal;

use Commune\Support\Message\AbsMessage;

/**
 * @property-read string $name
 * @property-read string $password
 */
class SignInfo extends AbsMessage
{
    const MAX_PASS_WD_LENGTH = 18;
    const MIN_PASS_WD_LENGTH = 4;
    const MAX_NAME_LENGTH = 10;

    public static function stub(): array
    {
        return [
            'name' => '',
            'password' => ''
        ];
    }

    public static function validate(array $data): ? string /* errorMsg */
    {

        $error = parent::validate($data);

        if (!empty($error)) {
            return $error;
        }

        $name = $data['name'] ?? '';

        if (empty($name)) {
            return "name should not be empty, $name given";
        }

        if (mb_strlen($name) > self::MAX_NAME_LENGTH) {
            return "name $name too long";
        }

        $passwd = $data['password'] ?? '';

        if (empty($passwd))

        if (strlen($passwd) > self::MAX_PASS_WD_LENGTH) {
            return 'password too long';
        }

        // password 可以为空, 表示没有密码, 但不能太短.
        if (!empty($passwd) && (strlen($passwd) < self::MIN_PASS_WD_LENGTH)) {
            return 'password too short';
        }

        return null;
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