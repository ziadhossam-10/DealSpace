<?php

namespace App\Services\PhoneNumber;

interface PhoneNumberServiceInterface
{
    public function normalize(string $phoneNumber): string;
    public function format(string $phoneNumber, string $format = 'E164'): string;
    public function isValid(string $phoneNumber): bool;
}
