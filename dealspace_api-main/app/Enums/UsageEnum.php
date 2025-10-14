<?php

namespace App\Enums;

enum UsageEnum: int
{
    case ONE_TO_TWO = 0;
    case THREE_TO_FIVE = 1;
    case SIX_TO_TEN = 2;
    case ELEVEN_TO_TWENTY = 3;
    case TWENTY_PLUS = 4;

    /**
     * Returns the label for a given status.
     *
     * @param string $status
     * @return string
     */
    public static function label(int $status = self::ONE_TO_TWO->value): string
    {
        return match ($status) {
            self::ONE_TO_TWO->value => '1 - 2 People',
            self::THREE_TO_FIVE->value => '3 - 5 People',
            self::SIX_TO_TEN->value => '6 - 10 People',
            self::ELEVEN_TO_TWENTY->value => '11 - 20 People',
            self::TWENTY_PLUS->value => '20+ People',
            default => 'unknown',
        };
    }

    /**
     * Returns an array of options for the UsageEnum suitable for use in a select form element.
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::ONE_TO_TWO->value => self::label(self::ONE_TO_TWO->value),
            self::THREE_TO_FIVE->value => self::label(self::THREE_TO_FIVE->value),
            self::SIX_TO_TEN->value => self::label(self::SIX_TO_TEN->value),
            self::ELEVEN_TO_TWENTY->value => self::label(self::ELEVEN_TO_TWENTY->value),
            self::TWENTY_PLUS->value => self::label(self::TWENTY_PLUS->value),
        ];
    }
}
