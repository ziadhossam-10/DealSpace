<?php

namespace App\Services\Auth;

use App\Enums\RoleEnum;
use App\Jobs\CreateDefaultAppointmentOutcomesJob;
use App\Jobs\CreateDefaultAppointmentTypesJob;
use App\Jobs\CreateDefaultDealTypesAndStagesJob;
use App\Jobs\CreateDefaultStagesJob;
use App\Jobs\PurchasePhoneForAccountJob;
use App\Models\Tenant;
use App\Repositories\Auth\AuthRepositoryInterface;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Auth\AuthenticationException;
use Laravel\Socialite\Facades\Socialite;
use Exception;
use Illuminate\Support\Str;

class AuthService implements AuthServiceInterface
{
    protected $authRepository;

    public function __construct(AuthRepositoryInterface $authRepository)
    {
        $this->authRepository = $authRepository;
    }

    /**
     * Generate a unique tenant ID
     *
     * @return string
     */
    protected function generateUniqueTenantId()
    {
        $tenantId = null;
        $attempts = 0;
        $maxAttempts = 5;

        do {
            // Generate a random string ID with increased length for more uniqueness
            $tenantId = Str::lower(Str::random(10));
            $exists = Tenant::where('id', $tenantId)->exists();
            $attempts++;

            // If we've tried too many times, throw an exception to prevent infinite loop
            if ($attempts >= $maxAttempts && $exists) {
                throw new Exception('Failed to generate a unique tenant ID after multiple attempts.');
            }
        } while ($exists);

        return $tenantId;
    }

    public function register(array $data)
    {
        // Wrap everything in a transaction
        if (isset($data['avatar']) && $data['avatar']) {
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        DB::beginTransaction();

        try {
            // Create a new tenant with a unique string ID
            $tenantId = $this->generateUniqueTenantId();
            $tenant = Tenant::create([
                'id' => $tenantId
            ]);

            // Add tenant_id to user data
            $data['tenant_id'] = $tenantId;

            $data['role'] = RoleEnum::OWNER;

            $user = $this->authRepository->create($data);
            $token = $this->authRepository->createToken($user);

            DB::commit();

            // add settings
            CreateDefaultStagesJob::dispatch($user);
            CreateDefaultDealTypesAndStagesJob::dispatch($user);
            CreateDefaultAppointmentTypesJob::dispatch($user);
            CreateDefaultAppointmentOutcomesJob::dispatch($user);
            // PurchasePhoneForAccountJob::dispatch($user, "US");

            return [
                'user' => $user,
                'token' => $token,
                'tenant' => $tenant
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function login(string $email, string $password)
    {
        $user = $this->authRepository->findByEmail($email);
        if (!$user || !Hash::check($password, $user->password)) {
            throw new AuthenticationException('The provided credentials are incorrect.');
        }

        $token = $this->authRepository->createToken($user);
        return [
            'user' => $user,
            'token' => $token
        ];
    }

    public function loginWithSocialProvider(string $provider, string $token)
    {
        try {
            // Set the token to Socialite
            $socialUser = Socialite::driver($provider)->stateless()->userFromToken($token);
            // Find existing user or create new one
            $user = $this->authRepository->findByProvider($provider, $socialUser->getId());
            if (!$user) {
                $user = $this->authRepository->findByEmail($socialUser->getEmail());
                if (!$user) {
                    // Wrap user and tenant creation in a transaction
                    return DB::transaction(function () use ($provider, $socialUser) {
                        // Create a new tenant with a unique string ID
                        $tenantId = $this->generateUniqueTenantId();
                        $tenant = Tenant::create([
                            'id' => $tenantId
                        ]);

                        // Create new user with tenant_id
                        $user = $this->authRepository->create([
                            'name' => $socialUser->getName(),
                            'email' => $socialUser->getEmail(),
                            'avatar' => $socialUser->getAvatar(),
                            'provider' => $provider,
                            'provider_id' => $socialUser->getId(),
                            'tenant_id' => $tenant->id,
                        ]);

                        $token = $this->authRepository->createToken($user);
                        return [
                            'user' => $user,
                            'token' => $token
                        ];
                    });
                } else {
                    // Update existing user with provider info
                    $user->provider = $provider;
                    $user->provider_id = $socialUser->getId();
                    $user->save();
                }
            }
            $token = $this->authRepository->createToken($user);
            return [
                'user' => $user,
                'token' => $token
            ];
        } catch (Exception $e) {
            throw new AuthenticationException('Failed to authenticate with ' . $provider . ': ' . $e->getMessage());
        }
    }

    public function updateProfile(array $data)
    {
        $user = Auth::user();
        if (!$user) {
            throw new AuthenticationException('User not authenticated.');
        }

        if (isset($data['avatar']) && $data['avatar']) {
            if ($user->avatar && !filter_var($user->avatar, FILTER_VALIDATE_URL)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $data['avatar'] = $this->uploadAvatar($data['avatar']);
        }

        $updatedUser = $this->authRepository->update($user->id, $data);
        return $updatedUser;
    }

    public function logout()
    {
        $user = Auth::user();
        if ($user) {
            $user->tokens()->delete();
        }
        return true;
    }

    /**
     * Upload avatar and return file path
     *
     * @param mixed $avatar
     * @return string
     */
    protected function uploadAvatar($avatar)
    {
        $path = $avatar->store('avatars', 'public');
        return $path;
    }
}
