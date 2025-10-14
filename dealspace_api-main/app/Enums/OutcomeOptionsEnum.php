<?php

namespace App\Enums;

enum OutcomeOptionsEnum: int
{
    case INTERESTED = 0;
    case NOT_INTERESTED = 1;
    case LEFT_MESSAGE = 2;
    case NO_ANSWER = 3;
    case BUSY = 4;
    case BAD_NUMBER = 5;

    /**
     * Get the label for a given status.
     *
     * @param int $status
     * @return string
     */
    public static function label(int $status = self::INTERESTED->value): string
    {
        return match ($status) {
            self::INTERESTED->value     => 'Interested',
            self::NOT_INTERESTED->value => 'Not Interested',
            self::LEFT_MESSAGE->value   => 'Left Message',
            self::NO_ANSWER->value      => 'No Answer',
            self::BUSY->value           => 'Busy',
            self::BAD_NUMBER->value     => 'Bad Number',
            default => 'Unknown',
        };
    }

    /**
     * Get all outcome options as [value => label].
     *
     * @return array
     */
    public static function options(): array
    {
        return [
            self::INTERESTED->value     => self::label(self::INTERESTED->value),
            self::NOT_INTERESTED->value => self::label(self::NOT_INTERESTED->value),
            self::LEFT_MESSAGE->value   => self::label(self::LEFT_MESSAGE->value),
            self::NO_ANSWER->value      => self::label(self::NO_ANSWER->value),
            self::BUSY->value           => self::label(self::BUSY->value),
            self::BAD_NUMBER->value     => self::label(self::BAD_NUMBER->value),
        ];
    }
}
