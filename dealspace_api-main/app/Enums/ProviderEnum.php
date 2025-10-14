<?php

namespace App\Enums;

enum ProviderEnum: int
{
    case GOOGLE = 0;

    /**
     * Returns the label for a given status.
     *
     * @param int $status
     * @return string
     */
    public static function label(int $status = self::GOOGLE->value): string
    {
        return match ($status) {
            self::GOOGLE->value => 'Google',
            default => 'unknown',
        };
    }

    /**
     * Returns an array of options for the ProviderEnum suitable for use in a select form element.
     *
     * @return array
     */

    public static function options(): array
    {
        return [
            self::GOOGLE->value => self::label(self::GOOGLE->value),
        ];
    }
}
