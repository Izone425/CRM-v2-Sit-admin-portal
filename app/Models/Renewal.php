<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Renewal extends Model
{
    use HasFactory;

    protected $fillable = [
        'lead_id',
        'f_company_id',
        'hr_version',
        'software_handover_id',
        'company_name',
        'expiry_date',
        'pi_numbers',
        'total_amount',
        'mapping_status',
        'admin_renewal',
        'renewal_progress',
        'reseller_status',
        'reseller_name',
        'notes',
        'progress_history',
        'follow_up_date',
        'follow_up_counter',
        'manual_follow_up_count',
        'task_status'
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'pi_numbers' => 'array',
        'progress_history' => 'array',
        'total_amount' => 'decimal:2',
        'task_status' => 'boolean',
    ];

    /**
     * Normalize f_company_id by stripping leading zeros on save.
     * This ensures consistent matching between crm_expiring_license (zero-padded)
     * and renewals table (non-padded).
     */
    public function setFCompanyIdAttribute($value)
    {
        // Only strip leading zeros for numeric IDs (v1), preserve SW_ prefix (v2)
        $this->attributes['f_company_id'] = is_numeric($value) ? (string) intval($value) : $value;
    }

    /**
     * Scope to match f_company_id regardless of zero-padding.
     * Uses CAST so '0000008782' and '8782' both match.
     */
    public function scopeWhereCompanyId($query, $companyId)
    {
        return $query->whereRaw('CAST(f_company_id AS UNSIGNED) = ?', [intval($companyId)]);
    }

    /**
     * Find renewal by company ID, handling zero-padded values in DB.
     */
    public static function findByCompanyId($companyId)
    {
        return static::whereCompanyId($companyId)->first();
    }

    /**
     * Update or create a renewal record, matching f_company_id regardless of zero-padding.
     */
    public static function updateOrCreateByCompanyId($companyId, array $attributes)
    {
        $existing = static::whereCompanyId($companyId)->first();

        if ($existing) {
            $existing->update($attributes);
            return $existing;
        }

        return static::create(array_merge(['f_company_id' => $companyId], $attributes));
    }

    // Relationship to Lead (many renewals to one lead)
    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    // Status options
    public static function getMappingStatusOptions(): array
    {
        return [
            'pending_mapping' => 'Pending Mapping',
            'completed_mapping' => 'Completed Mapping',
            'onhold_mapping' => 'OnHold Mapping',
        ];
    }

    public static function getRenewalProgressOptions(): array
    {
        return [
            'new' => 'New',
            'pending_confirmation' => 'Pending Confirmation',
            'pending_payment' => 'Pending Payment',
            'completed_renewal' => 'Completed Payment',
            'not_completed' => 'Not Completed',
        ];
    }

    public static function getResellerStatusOptions(): array
    {
        return [
            'none' => 'None',
            'available' => 'Available',
        ];
    }

    // Helper methods
    public function getMappingStatusLabelAttribute(): string
    {
        return self::getMappingStatusOptions()[$this->mapping_status] ?? $this->mapping_status;
    }

    public function getAdminRenewalLabelAttribute(): string
    {
        return self::getAdminRenewalOptions()[$this->admin_renewal] ?? $this->admin_renewal;
    }

    public function getRenewalProgressLabelAttribute(): string
    {
        return self::getRenewalProgressOptions()[$this->renewal_progress] ?? $this->renewal_progress;
    }

    public function getResellerStatusLabelAttribute(): string
    {
        return self::getResellerStatusOptions()[$this->reseller_status] ?? $this->reseller_status;
    }

    // Add progress history entry
    public function addProgressHistory(string $from, string $to, string $reason = null): void
    {
        $history = $this->progress_history ?? [];
        $history[] = [
            'from' => $from,
            'to' => $to,
            'reason' => $reason,
            'changed_at' => now(),
            'changed_by' => auth()->user()?->name ?? 'System',
        ];
        $this->update(['progress_history' => $history]);
    }

    // Scope methods
    public function scopeByMappingStatus($query, string $status)
    {
        return $query->where('mapping_status', $status);
    }

    public function scopeByRenewalProgress($query, string $progress)
    {
        return $query->where('renewal_progress', $progress);
    }

    public function scopeExpiringBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('expiry_date', [$startDate, $endDate]);
    }
}
