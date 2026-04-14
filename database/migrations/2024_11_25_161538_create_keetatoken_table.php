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
        Schema::create('keetatoken', function (Blueprint $table) {
            $table->id(); // auto-incrementing primary key
            $table->integer('brandId');
            $table->string('accessToken');  // stores the access token
            $table->string('tokenType');    // stores the token type (bearer)
            $table->bigInteger('expiresIn');   // stores the expiration time
            $table->string('refreshToken'); // stores the refresh token
            $table->string('scope');        // stores the scope (all)
            $table->bigInteger('issuedAtTime');  // stores the issued time (in milliseconds)
            $table->timestamps(); // to automatically handle created_at and updated_at timestamps
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('keetatoken');
    }
};
