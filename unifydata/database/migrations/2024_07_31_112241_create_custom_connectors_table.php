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
        Schema::create('custom_connectors', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('base_url');
            $table->string('auth_type');
            $table->json('auth_credentials')->nullable();
            $table->json('streams');
            $table->json('pagination')->nullable();
            $table->json('incremental_sync')->nullable();
            $table->enum('status',['draft', 'published'])->default('draft');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_connectors');
    }
};
