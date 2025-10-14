<?php

namespace App\Services\PhoneNumber;

class PhoneNumberService implements PhoneNumberServiceInterface
{
    /**
     * Normalize phone number for consistent comparison.
     */
    public function normalize(string $phoneNumber): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D/', '', $phoneNumber);

        // If it starts with 1 and has 11 digits, remove the 1 (US/Canada)
        if (strlen($digits) === 11 && substr($digits, 0, 1) === '1') {
            $digits = substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Format phone number to specified format.
     */
    public function format(string $phoneNumber, string $format = 'E164'): string
    {
        $normalized = $this->normalize($phoneNumber);

        switch ($format) {
            case 'E164':
                return '+1' . $normalized; // Assuming US/Canada numbers
            case 'NATIONAL':
                return '(' . substr($normalized, 0, 3) . ') ' .
                    substr($normalized, 3, 3) . '-' .
                    substr($normalized, 6);
            case 'DIGITS_ONLY':
                return $normalized;
            default:
                return $normalized;
        }
    }

    /**
     * Check if phone number is valid.
     */
    public function isValid(string $phoneNumber): bool
    {
        $normalized = $this->normalize($phoneNumber);
        return strlen($normalized) === 10 && is_numeric($normalized);
    }
}
