<?php

use App\Helpers\ApiResponder;
use App\Helpers\Base64Helper;

if (!function_exists('successResponse')) {
    /**
     * Returns a successful response with a status code of 200.
     *
     * @param string|null $message The response message.
     * @param mixed $data The response data.
     * @param int $statusCode The response status code.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function successResponse(?string $message = null, $data = null, int $statusCode = 200)
    {
        return ApiResponder::successResponse($message, $data, $statusCode);
    }
}

if (!function_exists('errorResponse')) {
    /**
     * Returns an error response with the specified message, status code, and errors.
     *
     * @param string|null $message The response message.
     * @param int $statusCode The response status code.
     * @param mixed $errors The errors related to the response.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    function errorResponse(?string $message = null, int $statusCode = 500, $errors = null)
    {
        return ApiResponder::errorResponse($message, $statusCode, $errors);
    }
}

if (!function_exists('validationErrorResponse')) {
    /**
     * Returns a validation error response with the specified errors, message, and status code.
     *
     * @param array $errors The validation errors.
     * @param string|null $message The response message.
     * @param int $statusCode The response status code.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    function validationErrorResponse($errors, ?string $message = null, int $statusCode = 422)
    {
        return ApiResponder::validationErrorResponse($errors, $message, $statusCode);
    }
}

if (!function_exists('base64url_encode')) {
    function base64url_encode($data)
    {
        return Base64Helper::encode($data);
    }
}

if (!function_exists('base64url_decode')) {
    function base64url_decode($data)
    {
        return Base64Helper::decode($data);
    }
}
