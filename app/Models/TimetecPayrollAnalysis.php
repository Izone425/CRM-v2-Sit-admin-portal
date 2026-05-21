<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TimetecPayrollAnalysis extends Model
{
    protected $table = 'timetec_payroll_analysis';

    protected $guarded = [];

    public $timestamps = false;

    const UPDATED_AT = 'last_updated';
    const CREATED_AT = 'created_date';
}
