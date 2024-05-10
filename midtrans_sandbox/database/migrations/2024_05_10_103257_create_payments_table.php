<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id')->nullable();
            $table->string('status')->nullable();
            $table->double('price')->nullable();
            $table->string('item_name')->nullable();
            $table->string('customer_first_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('checkout_link')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
