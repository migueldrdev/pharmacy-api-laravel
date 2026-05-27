<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->boolean('active')->default(true);
            $table->unsignedBigInteger('user_created')->nullable();
            $table->unsignedBigInteger('user_updated')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('batches', function (Blueprint $table) {
            $table->dropColumn(['active', 'user_created', 'user_updated']);
        });
    }
};