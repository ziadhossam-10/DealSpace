<?php

namespace App\Services\TextMessages;

interface TextMessageTwilioServiceInterface
{
    public function sendSms(string $to, string $message, string $from = null): array;
    public function validateWebhook(string $signature, string $url, array $data): bool;
    public function getMessageStatus(string $messageSid): array;
}
