<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ketaa_orders_backup', function (Blueprint $table) {
            $table->id();
            $table->string('keeta_order_id')->unique();
            $table->string('SDM_order_id')->nullable();
            $table->string('sig');
            $table->integer('event_id');
            $table->bigInteger('app_id');
            $table->string('message_id');
            $table->integer('shop_id');
            $table->text('message');
            $table->bigInteger('timestamp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ketaa_orders_backup');
    }
};
