<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FinancialYear extends Model
{
    use HasFactory;
    protected $table = 'financial_year';
    protected $fillable = [
        'name',
        'start_date',
        'end_date',
        'current_year_flag',
        'inv_prefix'
    ];
}
