<?php

namespace App\Enums;

enum GroupTypeEnum: int
{
    case AGENT = 0;
    case LENDER = 1;

    /**
     * Returns the label for a given status.
     *
     * @param string $status
     * @return string
     */
    public static function label($status = self::AGENT->value): string
    {
        return match ($status) {
            self::AGENT->value => 'Agent',
            self::LENDER->value => 'Lender',
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
            self::AGENT->value => self::label(self::AGENT->value),
            self::LENDER->value => self::label(self::LENDER->value),
        ];
    }
}
