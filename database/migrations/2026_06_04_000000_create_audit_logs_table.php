<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('auditable_type'); // Modelo afectado (App\Models\Product, etc.)
            $table->unsignedBigInteger('auditable_id'); // ID del modelo afectado
            $table->string('event'); // created, updated, deleted
            $table->json('old_values')->nullable(); // Valores anteriores
            $table->json('new_values')->nullable(); // Valores nuevos
            $table->unsignedBigInteger('user_id')->nullable(); // Usuario que realizó la acción
            $table->string('ip_address')->nullable(); // Dirección IP
            $table->text('user_agent')->nullable(); // User agent del navegador
            $table->timestamps();

            $table->index(['auditable_type', 'auditable_id']);
            $table->index('user_id');
            $table->index('event');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
