<?php

namespace App\Enums;

enum IndustryEnum: int
{
    case REAL_ESTATE = 0;
    case OTHER = 1;

    /**
     * Returns the label for a given status.
     *
     * @param string $status
     * @return string
     */
    public static function label(int $status = self::REAL_ESTATE->value): string
    {
        return match ($status) {
            self::REAL_ESTATE->value => 'Real Estate',
            self::OTHER->value => 'Other',
            default => 'unknown',
        };
    }

    /**
     * Returns an array of options for the IndustryEnum suitable for use in a select form element.
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::REAL_ESTATE->value => self::label(self::REAL_ESTATE->value),
            self::OTHER->value => self::label(self::OTHER->value),
        ];
    }
}
