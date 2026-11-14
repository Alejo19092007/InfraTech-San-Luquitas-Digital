<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Noticias y avisos de la institución (CRUD del Portal)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('novedades', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 150);
            $table->string('resumen', 300)->nullable();
            $table->text('contenido');
            $table->string('categoria', 40)->default('General');
            $table->date('fecha_publicacion');
            $table->timestamps();

            $table->index('fecha_publicacion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('novedades');
    }
};
