<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class ApiKey extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'name',
        'key',
        'allowed_domains',
        'allowed_endpoints',
        'last_used_at',
        'is_active',
    ];

    protected $casts = [
        'allowed_domains' => 'array',
        'allowed_endpoints' => 'array',
        'last_used_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected $hidden = [
        'key', // Hide the actual key from serialization for security
    ];

    public function updateLastUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    public function isDomainAllowed(string $domain): bool
    {
        if (empty($this->allowed_domains)) {
            return true; // No restrictions
        }

        return in_array($domain, $this->allowed_domains) ||
            in_array('*', $this->allowed_domains);
    }

    public function isEndpointAllowed(string $endpoint): bool
    {
        if (empty($this->allowed_endpoints)) {
            return true; // No restrictions
        }

        foreach ($this->allowed_endpoints as $allowedEndpoint) {
            if (
                $allowedEndpoint === '*' ||
                $endpoint === $allowedEndpoint ||
                fnmatch($allowedEndpoint, $endpoint)
            ) {
                return true;
            }
        }

        return false;
    }
}
