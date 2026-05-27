<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->unsignedBigInteger('batch_id')->nullable()->after('product_id');
            $table->foreign('batch_id')->references('id')->on('batches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_details', function (Blueprint $table) {
            $table->dropForeign(['batch_id']);
            $table->dropColumn('batch_id');
        });
    }
};