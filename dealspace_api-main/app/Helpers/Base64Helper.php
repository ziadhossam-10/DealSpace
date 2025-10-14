<?php

namespace App\Helpers;

class Base64Helper
{
    /**
     * Encodes data with base64 URL safe encoding.
     *
     * @param string $data
     * @return string
     */
    public static function encode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Decodes base64 URL safe encoded data.
     *
     * @param string $data
     * @return string|false
     */
    public static function decode(string $data): string|false
    {
        return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
    }
}
