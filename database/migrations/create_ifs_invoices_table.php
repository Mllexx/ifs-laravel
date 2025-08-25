<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('ifs_customers', function (Blueprint $table) {
            $table->id();

            $table->string('customer_id', 20)->primary();
            $table->string('name', 100);
            $table->date('creation_date');
            $table->string('association_no')->nullable();
            $table->string('party', 20)->nullable();
            $table->boolean('default_domain')->default(true);
            $table->string('default_language', 10)->default('en');
            $table->string('country', 10)->default('KE');
            $table->string('party_type', 50)->default('Customer');
            $table->string('corporate_form')->nullable();
            $table->string('identifier_reference')->nullable();
            $table->string('identifier_ref_validation', 50)->default('None');
            $table->string('picture_id')->nullable();
            $table->boolean('one_time')->default(false);
            $table->string('customer_category', 100)->default('Customer');
            $table->boolean('b2b_customer')->default(false);
            $table->string('customer_tax_usage_type')->nullable();
            $table->string('business_classification')->nullable();
            $table->date('date_of_registration')->nullable();
            $table->boolean('valid_data_processing_purpose')->default(false);
            $table->timestamp('last_sync')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('name');
            $table->index('customer_category');
            $table->index('country');


            $table->timestamps();
        });
    }

    public function down(){
        Schema::dropIfExists('ifs_customers');
    }
};
