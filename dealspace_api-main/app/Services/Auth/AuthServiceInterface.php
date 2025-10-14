<?php
namespace App\Services\Auth;

interface AuthServiceInterface
{
    public function register(array $data);
    public function login(string $email, string $password);
    public function loginWithSocialProvider(string $provider, string $token);
    public function updateProfile(array $data);
    public function logout();
}