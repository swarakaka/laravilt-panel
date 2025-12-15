<?php

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Collection;
use Laravilt\Panel\Contracts\HasDefaultTenant;
use Laravilt\Panel\Contracts\HasTenants;
use Laravilt\Panel\Panel;

trait HasTeams
{
    /**
     * Get all teams that the user belongs to.
     */
    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class, 'team_user')
            ->withPivot('role')
            ->withTimestamps();
    }

    /**
     * Get the user's current team.
     */
    public function currentTeam()
    {
        return $this->belongsTo(Team::class, 'current_team_id');
    }

    /**
     * Get all tenants that the user can access for the given panel.
     */
    public function getTenants(Panel $panel): Collection
    {
        return $this->teams;
    }

    /**
     * Check if the user can access the given tenant.
     */
    public function canAccessTenant(Model $tenant): bool
    {
        return $this->teams()->whereKey($tenant->getKey())->exists();
    }

    /**
     * Get the default tenant for the given panel.
     */
    public function getDefaultTenant(Panel $panel): ?Model
    {
        // Return current team if set, otherwise first team
        return $this->currentTeam ?? $this->teams()->first();
    }

    /**
     * Check if the user owns the given team.
     */
    public function ownsTeam(Team $team): bool
    {
        return $this->id === $team->owner_id;
    }

    /**
     * Get the user's role in a specific team.
     */
    public function teamRole(Team $team): ?string
    {
        $membership = $this->teams()->whereKey($team->getKey())->first();

        return $membership?->pivot?->role;
    }

    /**
     * Check if the user has a specific role in a team.
     */
    public function hasTeamRole(Team $team, string $role): bool
    {
        return $this->teamRole($team) === $role;
    }

    /**
     * Switch to a different team.
     */
    public function switchTeam(Team $team): bool
    {
        if (! $this->canAccessTenant($team)) {
            return false;
        }

        $this->forceFill([
            'current_team_id' => $team->id,
        ])->save();

        return true;
    }
}
