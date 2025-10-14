<?php

namespace App\Services\Auth;

use App\Models\User;

interface TwilioPhoneNumberServiceInterface
{
    /**
     * Provision a Twilio phone number for the given user.
     *
     * @param User $user
     * @param string $countryCode
     * @param string|null $areaCode
     * @return array|null
     */
    public function provisionPhoneNumberForUser(User $user, string $countryCode = 'US', string $areaCode = null): ?array;

    /**
     * Release a Twilio phone number using its SID.
     *
     * @param string $phoneSid
     * @return bool
     */
    public function releasePhoneNumber(string $phoneSid): bool;

    /**
     * Get details about a specific Twilio phone number.
     *
     * @param string $phoneSid
     * @return array|null
     */
    public function getPhoneNumberDetails(string $phoneSid): ?array;
}
