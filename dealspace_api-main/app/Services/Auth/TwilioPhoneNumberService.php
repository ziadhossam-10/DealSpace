<?php

namespace App\Services\Auth;

use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class TwilioPhoneNumberService implements TwilioPhoneNumberServiceInterface
{
    protected $twilio;
    protected $accountSid;
    protected $authToken;

    public function __construct()
    {
        $this->accountSid = config('services.twilio.account_sid');
        $this->authToken = config('services.twilio.auth_token');
        $this->twilio = new Client($this->accountSid, $this->authToken);
    }

    /**
     * Automatically provision a phone number for a new user
     *
     * @param User $user
     * @param string $countryCode (e.g., 'US', 'GB', 'CA')
     * @param string $areaCode (optional, e.g., '415' for San Francisco)
     * @return array|null
     */
    public function provisionPhoneNumberForUser(User $user, string $countryCode = 'US', ?string $areaCode = null): ?array
    {
        try {
            // Step 1: Search for available phone numbers
            $availableNumbers = $this->searchAvailableNumbers($countryCode, $areaCode);

            if (empty($availableNumbers)) {
                Log::warning("No available phone numbers found for user: {$user->id}");
                return null;
            }

            // Step 2: Purchase the first available number
            $selectedNumber = $availableNumbers[0];
            $purchasedNumber = $this->purchasePhoneNumber($selectedNumber->phoneNumber, $user);

            if ($purchasedNumber) {
                // Step 3: Save the number to user record
                $user->update([
                    'twilio_phone_number' => $purchasedNumber['phone_number'],
                    'twilio_phone_sid' => $purchasedNumber['sid'],
                ]);

                Log::info("Phone number {$purchasedNumber['phone_number']} provisioned for user: {$user->id}");

                return $purchasedNumber;
            }
        } catch (TwilioException $e) {
            Log::error("Twilio error provisioning number for user {$user->id}: " . $e->getMessage());
        } catch (\Exception $e) {
            Log::error("General error provisioning number for user {$user->id}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Search for available phone numbers
     *
     * @param string $countryCode
     * @param string|null $areaCode
     * @return array
     */
    protected function searchAvailableNumbers(string $countryCode, string $areaCode = null): array
    {
        $searchCriteria = [
            'SmsEnabled' => true,
            'VoiceEnabled' => true,
        ];

        if ($areaCode) {
            $searchCriteria['AreaCode'] = $areaCode;
        }

        $numbers = $this->twilio->availablePhoneNumbers($countryCode)
            ->local
            ->read($searchCriteria, 5); // Get up to 5 options

        return $numbers;
    }

    /**
     * Purchase a phone number
     *
     * @param string $phoneNumber
     * @param User $user
     * @return array|null
     */
    protected function purchasePhoneNumber(string $phoneNumber, User $user): ?array
    {
        try {
            $incomingPhoneNumber = $this->twilio->incomingPhoneNumbers->create([
                'phoneNumber' => $phoneNumber,
                'friendlyName' => "User: {$user->name} - {$phoneNumber}",
                'smsUrl' => route('twilio.webhook.sms', ['user' => $user->id]),
                'voiceUrl' => route('twilio.webhook.voice', ['user' => $user->id]),
                'smsMethod' => 'POST',
                'voiceMethod' => 'POST',
            ]);

            return [
                'sid' => $incomingPhoneNumber->sid,
                'phone_number' => $incomingPhoneNumber->phoneNumber,
                'friendly_name' => $incomingPhoneNumber->friendlyName,
            ];
        } catch (TwilioException $e) {
            Log::error("Failed to purchase phone number {$phoneNumber}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Release a phone number when user is deleted
     *
     * @param string $phoneSid
     * @return bool
     */
    public function releasePhoneNumber(string $phoneSid): bool
    {
        try {
            $this->twilio->incomingPhoneNumbers($phoneSid)->delete();
            Log::info("Phone number with SID {$phoneSid} released successfully");
            return true;
        } catch (TwilioException $e) {
            Log::error("Failed to release phone number {$phoneSid}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get phone number details
     *
     * @param string $phoneSid
     * @return array|null
     */
    public function getPhoneNumberDetails(string $phoneSid): ?array
    {
        try {
            $phoneNumber = $this->twilio->incomingPhoneNumbers($phoneSid)->fetch();

            return [
                'sid' => $phoneNumber->sid,
                'phone_number' => $phoneNumber->phoneNumber,
                'friendly_name' => $phoneNumber->friendlyName,
                'capabilities' => $phoneNumber->capabilities,
                'status' => $phoneNumber->status,
            ];
        } catch (TwilioException $e) {
            Log::error("Failed to fetch phone number details {$phoneSid}: " . $e->getMessage());
            return null;
        }
    }
}
