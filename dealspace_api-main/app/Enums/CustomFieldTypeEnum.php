<?php

namespace App\Enums;

enum CustomFieldTypeEnum: int
{
    case TEXT = 0;
    case DATE = 1;
    case NUMBER = 2;
    case DROPDOWN = 3;

    /**
     * Returns the label for a given status.
     *
     * @param string $status
     * @return string
     */
    public static function label($status = self::TEXT->value): string
    {
        return match ($status) {
            self::TEXT->value => 'text',
            self::DATE->value => 'date',
            self::NUMBER->value => 'number',
            self::DROPDOWN->value => 'dropdown',
            default => 'unknown',
        };
    }

    /**
     * Returns an array of options for the RoleEnum suitable for use in a select form element.
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::TEXT->value => self::label(self::TEXT->value),
            self::DATE->value => self::label(self::DATE->value),
            self::NUMBER->value => self::label(self::NUMBER->value),
            self::DROPDOWN->value => self::label(self::DROPDOWN->value),
        ];
    }
}
