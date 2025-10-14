<?php

namespace App\Enums;

enum RoleEnum: int
{
    case OWNER = 0;
    case ADMIN = 1;
    case AGENT = 2;
    case LENDER = 3;
    case ISAs = 4;

    /**
     * Returns the label for a given status.
     *
     * @param string $status
     * @return string
     */
    public static function label(int $status = self::OWNER->value): string
    {
        return match ($status) {
            self::OWNER->value => 'Owner',
            self::ADMIN->value => 'Admin',
            self::AGENT->value => 'Agent',
            self::LENDER->value => 'Lender',
            self::ISAs->value => 'Lead nurturing specialists',
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
            self::OWNER->value => self::label(self::OWNER->value),
            self::ADMIN->value => self::label(self::ADMIN->value),
            self::AGENT->value => self::label(self::AGENT->value),
            self::LENDER->value => self::label(self::LENDER->value),
            self::ISAs->value => self::label(self::ISAs->value),
        ];
    }

    /**
     * Get value of the enum case.
     *
     * @return int
     */
    public static function value(string $case): int
    {
        return match ($case) {
            'OWNER' => self::OWNER->value,
            'Owner' => self::OWNER->value,
            'owner' => self::OWNER->value,
            'ADMIN' => self::ADMIN->value,
            'Admin' => self::ADMIN->value,
            'admin' => self::ADMIN->value,
            'AGENT' => self::AGENT->value,
            'Agent' => self::AGENT->value,
            'agent' => self::AGENT->value,
            'LENDER' => self::LENDER->value,
            'Lender' => self::LENDER->value,
            'lender' => self::LENDER->value,
            'ISAs' => self::ISAs->value,
            'isas' => self::ISAs->value,
            'ISAS' => self::ISAs->value,
            default => throw new \InvalidArgumentException("Invalid role case: $case"),
        };
    }
}
