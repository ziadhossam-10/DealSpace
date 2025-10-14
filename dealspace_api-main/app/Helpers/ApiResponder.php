<?php

namespace App\Helpers;

class ApiResponder
{
    /**
     * Returns a successful response with a message and optional data.
     *
     * @param string $message
     * @param mixed $data
     * @param int $code
     * @return \Illuminate\Http\Response
     */
    public static function successResponse($message = 'Success', $data = null,  $code = 200)
    {
        return response()->json([
            'status' => true,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * Returns an error response with a message, status code, and optional errors.
     *
     * @param string $message The response message.
     * @param int $code The response status code.
     * @param mixed $errors The errors related to the response.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public static function errorResponse($message = 'Error', $code = 500, $errors = null)
    {
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }

    /**
     * Returns a validation error response with the specified errors, message, and status code.
     *
     * @param mixed $errors The validation errors.
     * @param string $message The response message.
     * @param int $code The response status code.
     *
     * @return \Illuminate\Http\JsonResponse
     */

    public static function validationErrorResponse($errors, $message = 'Validation error', $code = 422)
    {
        return self::errorResponse($message, $code, $errors);
    }
}
