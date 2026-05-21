<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class TicketingUser extends Model
{
    protected $connection = 'ticketingsystem_live';
    protected $table = 'users';

    protected $fillable = [
        'name',
        'email',
        'department_id',
    ];

    /**
     * Scope: get users by exact Spatie role name
     * Joins model_has_roles + roles on ticketingsystem_live
     */
    public function scopeByRole($query, string $roleName)
    {
        return $query->whereIn('id', function ($sub) use ($roleName) {
            $sub->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('roles.name', $roleName);
        });
    }

    /**
     * Scope: get users by LIKE role name match
     * Used for vague roles like "Manager", "Lead" that match multiple role names
     */
    public function scopeByRoleLike($query, string $roleName)
    {
        return $query->whereIn('id', function ($sub) use ($roleName) {
            $sub->select('model_id')
                ->from('model_has_roles')
                ->join('roles', 'roles.id', '=', 'model_has_roles.role_id')
                ->where('model_has_roles.model_type', 'App\\Models\\User')
                ->where('roles.name', 'LIKE', "%{$roleName}%");
        });
    }

    /**
     * Check if user has access to a specific product
     */
    public function canAccessProduct(int $productId): bool
    {
        return DB::connection('ticketingsystem_live')
            ->table('user_product_modules_access')
            ->where('user_id', $this->id)
            ->where('product_id', $productId)
            ->exists();
    }

    /**
     * Check if user has access to a specific product + module combination
     */
    public function canAccessModule(int $productId, int $moduleId): bool
    {
        return DB::connection('ticketingsystem_live')
            ->table('user_product_modules_access')
            ->where('user_id', $this->id)
            ->where('product_id', $productId)
            ->where('module_id', $moduleId)
            ->exists();
    }

    /**
     * Get accessible solution IDs for a given product
     */
    public function getAccessibleSolutionIds(int $productId): array
    {
        return DB::connection('ticketingsystem_live')
            ->table('user_product_modules_access')
            ->where('user_id', $this->id)
            ->where('product_id', $productId)
            ->whereNotNull('solution_id')
            ->pluck('solution_id')
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Department relationship (ticketingsystem_live.departments)
     */
    public function department(): BelongsTo
    {
        return $this->belongsTo(TicketingDepartment::class, 'department_id');
    }

    /**
     * Find matching CRM user by email (for in-app/push notifications)
     */
    public function getCrmUser(): ?User
    {
        return User::where('email', $this->email)->first();
    }

    public function freshTimestamp(): \Illuminate\Support\Carbon
    {
        return now()->subHours(8);
    }
}
