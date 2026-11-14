<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tareas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudiante_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('creada_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('materia', 60);
            $table->text('descripcion');
            $table->date('fecha_entrega');
            $table->string('estado', 12)->default('pendiente');   // pendiente | entregada
            $table->string('origen', 12)->default('web');         // whatsapp | web
            $table->timestamp('entregada_at')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'estado', 'fecha_entrega']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tareas');
    }
};
