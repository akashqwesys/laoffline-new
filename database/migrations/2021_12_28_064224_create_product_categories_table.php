<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCategoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->integer('id');
            $table->integer('product_default_category_id')->default('0');
            $table->string('name')->nullable();
            $table->integer('main_category_id')->default('0');
            $table->jsonb('company_id')->nullable();
            $table->integer('product_fabric_id')->nullable();
            $table->integer('sort_order')->default('0');
            $table->integer('multiple_company')->default('0');
            $table->string('rate')->nullable();
            $table->integer('is_delete')->default('0');
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
        Schema::dropIfExists('product_categories');
    }
}
