<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HrSalesInvoiceItem extends Model
{
    protected $table = 'hr_sales_invoice_items';

    protected $fillable = [
        'hr_sales_invoice_id',
        'product_id',
        'product_code',
        'description',
        'license_type',
        'quantity',
        'subscription_period',
        'license_start_date',
        'license_end_date',
        'unit_price',
        'discount',
        'taxation',
        'tax_code',
        'year',
        'tariff_code',
        'total_before_tax',
        'total_after_tax',
        'sort_order',
    ];

    protected $casts = [
        'license_start_date' => 'date',
        'license_end_date' => 'date',
        'unit_price' => 'decimal:2',
        'discount' => 'decimal:2',
        'taxation' => 'decimal:2',
        'total_before_tax' => 'decimal:2',
        'total_after_tax' => 'decimal:2',
    ];

    public function salesInvoice()
    {
        return $this->belongsTo(HrSalesInvoice::class, 'hr_sales_invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
