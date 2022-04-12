<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Reference extends Model
{
    use HasFactory;
    protected $fillable = [
        'reference_id',
        'employe_id',
        'financial_year_id', 
        'inward_or_outward', 
        'type_of_inward', 
        'company_id', 
        'selection_date', 
        'from_name', 
        'from_number', 
        'receiver_number', 
        'from_email_id', 
        'receiver_email_id', 
        'latter_by_id', 
        'courier_name', 
        'weight_of_parcel', 
        'courier_receipt_no', 
        'courier_received_time', 
        'delivery_by', 
        'mark_as_sample', 
        'gmail_mail_id', 
        'gmail_folder_name', 
        'enjay_uniqueid', 
        'is_deleted'
    ];
}
