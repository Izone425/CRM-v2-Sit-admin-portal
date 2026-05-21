<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FinanceInvoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'fc_number',
        'reseller_handover_id',
        'handover_id',
        'autocount_invoice_number',
        'timetec_invoice_number',
        'reseller_name',
        'subscriber_name',
        'reseller_commission_amount',
        'portal_type',
        'status',
        'created_by',
        'currency',
        'currency_rate',
    ];

    protected $casts = [
        'reseller_commission_amount' => 'decimal:2',
    ];

    protected $appends = ['formatted_id'];

    /**
     * Set the reseller name to uppercase
     */
    public function setResellerNameAttribute($value)
    {
        $this->attributes['reseller_name'] = strtoupper($value);
    }

    /**
     * Set the subscriber name to uppercase
     */
    public function setSubscriberNameAttribute($value)
    {
        $this->attributes['subscriber_name'] = strtoupper($value);
    }

    /**
     * Set the TimeTec invoice number to uppercase
     */
    public function setTimetecInvoiceNumberAttribute($value)
    {
        $this->attributes['timetec_invoice_number'] = strtoupper($value);
    }

    /**
     * Get formatted ID attribute: FC{YY}{MM}-{XXXX}
     * From 1 March 2026 onwards, derives from autocount_invoice_number (e.g. ERIN2603-0110 → FC2603-0110)
     * Before that, uses sequential numbering per month
     */
    public function getFormattedIdAttribute()
    {
        if (!$this->id || !$this->created_at) {
            return null;
        }

        // Use stored fc_number if available
        if (!empty($this->fc_number)) {
            return $this->fc_number;
        }

        // Legacy fallback: sequential numbering per month (for old records without fc_number)
        $year = $this->created_at->format('y');
        $month = $this->created_at->format('m');

        $monthStart = $this->created_at->copy()->startOfMonth();
        $monthEnd = $this->created_at->copy()->endOfMonth();

        $sequentialNumber = self::whereBetween('created_at', [$monthStart, $monthEnd])
            ->where('id', '<=', $this->id)
            ->count();

        return 'FC' . $year . $month . '-' . str_pad($sequentialNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function resellerHandover(): BelongsTo
    {
        return $this->belongsTo(ResellerHandover::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function softwareHandover(): BelongsTo
    {
        return $this->belongsTo(SoftwareHandover::class, 'handover_id');
    }

    public function hardwareHandover(): BelongsTo
    {
        return $this->belongsTo(HardwareHandoverV2::class, 'handover_id');
    }

    public function headcountHandover(): BelongsTo
    {
        return $this->belongsTo(HeadcountHandover::class, 'handover_id');
    }

    public function adminPortalInvoices()
    {
        return $this->hasMany(AdminPortalInvoice::class, 'finance_invoice_id');
    }

    /**
     * Generate FC Number from autocount_invoice_number
     * Replaces prefix (e.g. ERIN, EHIN, EPIN) with FC
     * Falls back to sequential FC{YY}{MM}-{XXXX} if no autocount number provided
     */
    public static function generateFcNumber(string $portalType = 'reseller', ?string $autocountInvoiceNumber = null): string
    {
        // From March 2026 onwards, derive from autocount invoice number
        if (!empty($autocountInvoiceNumber)) {
            return preg_replace('/^[A-Z]+/', 'FC', $autocountInvoiceNumber);
        }

        // Legacy: sequential numbering, global across portal types per month
        $yearMonth = now()->format('ym');

        $latestInvoice = self::where('fc_number', 'LIKE', "FC{$yearMonth}-%")
            ->orderByRaw('CAST(SUBSTRING(fc_number, -4) AS UNSIGNED) DESC')
            ->first();

        $nextNumber = 1;
        if ($latestInvoice) {
            preg_match("/FC{$yearMonth}-(\d+)/", $latestInvoice->fc_number, $matches);
            $nextNumber = (isset($matches[1]) ? intval($matches[1]) : 0) + 1;
        }

        return 'FC' . $yearMonth . '-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }
}
