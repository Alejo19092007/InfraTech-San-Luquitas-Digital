<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // admin | profesor | acudiente | estudiante
            $table->string('rol', 20)->default('estudiante')->after('password')->index();
            $table->unsignedTinyInteger('grado')->nullable()->after('rol');
            // Formato E.164 (+573001234567). Único: un número pertenece a una sola cuenta.
            $table->string('telefono_whatsapp', 20)->nullable()->unique()->after('grado');
            $table->timestamp('telefono_verificado_at')->nullable()->after('telefono_whatsapp');
        });

        // Un acudiente puede tener varios hijos, y un estudiante varios acudientes.
        Schema::create('acudiente_estudiante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acudiente_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('users')->cascadeOnDelete();
            $table->unique(['acudiente_id', 'estudiante_id']);
        });

        // Grados (y materia) que dicta cada profesor.
        Schema::create('asignaciones_profesor', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profesor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedTinyInteger('grado');
            $table->string('materia', 60)->nullable();
            $table->unique(['profesor_id', 'grado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_profesor');
        Schema::dropIfExists('acudiente_estudiante');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['rol', 'grado', 'telefono_whatsapp', 'telefono_verificado_at']);
        });
    }
};
