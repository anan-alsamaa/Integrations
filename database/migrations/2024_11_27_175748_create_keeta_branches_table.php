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
        Schema::create('keeta_branches', function (Blueprint $table) {
            $table->id(); // Auto-incrementing primary key
            $table->string('branch_name')->unique(); // Name of the branch
            $table->string('pos_key')->unique(); // POS key, ensuring it's unique
            $table->string('keeta_id')->unique(); // Keeta ID (e.g., external system ID)
            $table->integer('brand_id'); // Brand ID
            $table->timestamps(); // Created at and Updated at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keeta_branches');
    }
};
