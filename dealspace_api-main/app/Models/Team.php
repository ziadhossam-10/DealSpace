<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;
use App\Scopes\TeamScopes;
use App\Models\User;

class Team extends Model
{
    use HasFactory, BelongsToTenant, TeamScopes;

    protected $fillable = [
        'name',
    ];

    /**
     * The users that belong to the team.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_users', 'team_id', 'user_id');
    }

    /**
     * The leaders of the team.
     */
    public function leaders(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_leaders', 'team_id', 'user_id');
    }

    /**
     * Get all agent users for this team
     */
    public function agents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_users', 'team_id', 'user_id')
            ->where('role', \App\Enums\RoleEnum::AGENT);
    }

    /**
     * Scope to get teams with their agent counts
     */
    public function scopeWithAgentCounts($query)
    {
        return $query->withCount(['users as agent_count' => function ($q) {
            $q->where('role', \App\Enums\RoleEnum::AGENT);
        }]);
    }
}
