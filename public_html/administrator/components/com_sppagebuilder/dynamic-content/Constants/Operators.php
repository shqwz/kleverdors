<?php

namespace JoomShaper\SPPageBuilder\DynamicContent\Constants;

final class Operators
{
    /**
     * All the available SQL where clause operators.
     *
     * @var string
     * @since 5.5.0
     */
    public const EQUAL = '=';
    public const NOT_EQUAL = '!=';
    public const GREATER_THAN = '>';
    public const LESS_THAN = '<';
    public const GREATER_THAN_OR_EQUAL = '>=';
    public const LESS_THAN_OR_EQUAL = '<=';
    public const LIKE = 'LIKE';
    public const NOT_LIKE = 'NOT LIKE';
    public const IN = 'IN';
    public const NOT_IN = 'NOT IN';
    public const BETWEEN = 'BETWEEN';
    public const IS_NULL = 'IS NULL';
    public const IS_NOT_NULL = 'IS NOT NULL';

    /**
     * Get all operators.
     *
     * @return array
     * @since 5.5.0
     */
    public static function all(): array
    {
        return [
            self::EQUAL,
            self::NOT_EQUAL,
            self::GREATER_THAN,
            self::LESS_THAN,
            self::GREATER_THAN_OR_EQUAL,
            self::LESS_THAN_OR_EQUAL,
            self::LIKE,
            self::NOT_LIKE,
            self::IN,
            self::NOT_IN,
            self::BETWEEN,
            self::IS_NULL,
            self::IS_NOT_NULL,
        ];
    }
}
