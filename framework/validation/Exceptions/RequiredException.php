<?php


namespace Framework\Validation\Exceptions;

use Respect\Validation\Exceptions\ValidationException;

class RequiredException extends ValidationException {

    public static $defaultTemplates = [
        self::MODE_DEFAULT => [
            self::STANDARD => '{{name}} 是必填的',
        ],
        self::MODE_NEGATIVE => [
            self::STANDARD => '{{name}} 是必填的',
        ],
    ];
}
