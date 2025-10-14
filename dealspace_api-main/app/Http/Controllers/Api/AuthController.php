<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\SocialLoginRequest;
use App\Http\Requests\Auth\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthServiceInterface;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

class AuthController extends Controller
{

    protected $authService;


    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Registers a new user
     *
     * @param \App\Http\Requests\Auth\RegisterRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $result = $this->authService->register($request->validated());

        return successResponse('User registered successfully', [
            'user' => new UserResource($result['user']),
            'token' => $result['token']
        ]);
    }

    /**
     * Authenticates an existing user
     *
     * @param \App\Http\Requests\Auth\LoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $result = $this->authService->login(
            $request->email,
            $request->password
        );

        return successResponse('User logged in successfully', [
            'user' => new UserResource($result['user']),
            'token' => $result['token']
        ]);
    }

    /**
     * Authenticates an existing user through social providers
     *
     * @param \App\Http\Requests\Auth\SocialLoginRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function socialLogin(SocialLoginRequest $request)
    {
        $result = $this->authService->loginWithSocialProvider(
            $request->provider,
            $request->token
        );

        return successResponse('User logged in successfully with ' . ucfirst($request->provider), [
            'user' => new UserResource($result['user']),
            'token' => $result['token']
        ]);
    }

    /**
     * Updates the profile of the authenticated user
     *
     * @param \App\Http\Requests\Auth\UpdateProfileRequest $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(UpdateProfileRequest $request)
    {
        $updatedUser = $this->authService->updateProfile($request->validated());

        return successResponse(
            'Profile updated successfully',
            new UserResource($updatedUser)
        );
    }

    /**
     * Logs out the user from the application.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        $this->authService->logout();

        return successResponse('User logged out successfully');
    }

    /**
     * Returns the profile of the authenticated user
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return successResponse(
            'User profile retrieved successfully',
            new UserResource($request->user())
        );
    }

    /**
     * Throws an AuthenticationException to indicate that the user is unauthenticated.
     *
     * @throws \Illuminate\Auth\AuthenticationException
     */

    public function notAuthenticated()
    {
        throw new AuthenticationException('Unauthenticated');
    }
}