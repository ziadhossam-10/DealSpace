<?php

namespace App\Enums;

enum GroupDistributionEnum: int
{
    case FIRST_TO_CLAIM = 0;
    case ROUND_ROBIN = 1;

    /**
     * Returns the label for a given status.
     *
     * @param string $status
     * @return string
     */
    public static function label($status = self::FIRST_TO_CLAIM->value): string
    {
        return match ($status) {
            self::FIRST_TO_CLAIM->value => 'First to Claim',
            self::ROUND_ROBIN->value => 'Round Robin',
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
            self::FIRST_TO_CLAIM->value => self::label(self::FIRST_TO_CLAIM->value),
            self::ROUND_ROBIN->value => self::label(self::ROUND_ROBIN->value),
        ];
    }
}
