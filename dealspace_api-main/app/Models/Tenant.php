<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Contracts\Tenant as TenantContract;
use Stancl\Tenancy\Database\Concerns\HasDataColumn;

class Tenant extends Model implements TenantContract
{
    use HasFactory, HasDataColumn;

    protected $table = 'tenants';
    protected $primaryKey = 'id';
    protected $keyType = 'string';
    public $incrementing = false;
    protected $fillable = ['id', 'data'];

    /**
     * Get the users associated with the tenant.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get the domains associated with the tenant.
     */
    public function domains(): HasMany
    {
        return $this->hasMany(Domain::class);
    }

    /**
     * Get internal data.
     */
    public function getInternal(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Set internal data.
     */
    public function setInternal(string $key, $value): void
    {
        $this->data[$key] = $value;
    }

    /**
     * Run a specific operation.
     */
    public function run(callable $callback)
    {
        // Implement the logic for the operation here
        return null;
    }

    // Required methods from TenantContract
    public function getTenantKeyName(): string
    {
        return 'id';
    }

    public function getTenantKey()
    {
        return $this->getAttribute($this->getTenantKeyName());
    }

    public function getCentralConnection(): string
    {
        return config('database.default');
    }
}
