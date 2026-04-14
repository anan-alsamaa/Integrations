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
        Schema::create('ninja_branches', function (Blueprint $table) {
            $table->id();  // Auto-incrementing primary key
            $table->string('pos_key');  // String column for pos_key
            $table->string('branch_name');  // String column for branch_name
            $table->timestamps();  // Adds created_at and updated_at columns
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ninja_branches');
    }
};
