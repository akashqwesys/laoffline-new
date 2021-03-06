<?php

namespace App\Models\comboids;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comboids extends Model
{
    use HasFactory;

    protected $primaryKey = 'comboid';

    protected $fillable = [
            'iuid',
            'ouid',
            'general_ref_id',
            'follow_as_inward_or_outward',
            'system_module_id',
            // 'main_or_followup',
            'generated_by',
            'updated_by',
            'inward_or_outward_flag',
            'inward_or_outward_id',
            'sale_bill_id',
            'payment_id',
            // 'payment_followup_id',
            'goods_return_id',
            // 'good_return_followup_id',
            'commission_id',
            // 'commission_followup_id',
            'commission_invoice_id',
            'is_invoice',
            'sample_id',
            'company_id',
            'supplier_id',
            'inward_ref_via',
            'company_type',
            'inform_md',
            'followup_via',
            'inward_or_outward_via',
            'selection_date',
            'from_name',
            'from_number',
            'receiver_number',
            'from_email_id',
            'receiver_email_id',
            'new_or_old_inward_or_outward',
            'subject',
            'attachments',
            'outward_attachments',
            'outward_employe_id',
            'default_category_id',
            'main_category_id',
            'agent_id',
            'supplier_invoice_no',
            'total',
            'sale_bill_flag',
            'receipt_mode',
            'receipt_amount',
            'tds',
            'net_received_amount',
            'received_commission_amount',
            'action_date',
            'action_instruction',
            // 'next_follow_up_date',
            // 'next_follow_up_time',
            'assigned_to',
            'remark',
            'being_late',
            'financial_year_id',
            'system_url',
            // 'required_followup',
            'enjay_uniqueid',
            'is_completed',
            'mark_as_draft',
            'color_flag_id',
            'product_qty',
            'fabric_meters',
            'sample_return_qty',
            'mobile_flag',
            'is_deleted',
    ];
}
