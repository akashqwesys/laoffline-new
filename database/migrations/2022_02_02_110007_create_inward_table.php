<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateInwardTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('inwards', function (Blueprint $table) {
            $table->id('inward_id');
            $table->integer('iuid')->default(0);
            $table->string('call_by', 100)->nullable();
            $table->integer('inward_ref_via')->default(0);
            $table->integer('general_input_ref_id')->default(0);
            $table->integer('new_or_old_inward')->default(0);
            $table->integer('financial_year_id')->default(0);
            $table->integer('connected_inward')->default(0);
            $table->date('inward_date')->nullable();
            $table->text('subject')->nullable();
            $table->integer('employee_id')->default(0);
            $table->string('type_of_inward')->nullable();
            $table->string('receiver_number')->nullable();
            $table->string('from_number')->nullable();
            $table->integer('company_id')->default(0);
            $table->integer('supplier_id')->default(0);
            $table->string('courier_name')->nullable();
            $table->string('weight_of_parcel')->nullable();
            $table->string('courier_receipt_no')->nullable();
            $table->dateTime('courier_received_time')->nullable();
            $table->string('from_name')->nullable();
            $table->jsonb('attachments')->nullable();
            $table->text('remarks')->nullable();
            $table->integer('latter_by_id')->default(0);
            $table->string('delivery_by')->nullable();
            $table->string('receiver_email_id')->nullable();
            $table->string('from_email_id')->nullable();
            $table->integer('product_main_id')->default(0);
            $table->jsonb('product_image_id')->nullable();
            $table->integer('inward_link_with_id')->default(0);
            $table->integer('enquiry_complain_for')->default(0);
            $table->text('client_remark')->nullable();
            $table->integer('notify_client')->default(0);
            $table->integer('notify_md')->default(0);
            $table->integer('required_followup')->default(0);
            $table->string('delivery_period')->nullable();
            $table->string('to_name')->nullable();
            $table->integer('mark_as_draft')->default(0);
            $table->string('sample_via',32)->nullable();
            $table->integer('sample_for')->default(0);
            $table->text('sample_prod_or_fabric')->nullable();
            $table->integer('product_qty')->default(0);
            $table->integer('fabric_meters')->default(0);
            $table->tinyInteger('is_deleted');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('inwards');
    }
}
