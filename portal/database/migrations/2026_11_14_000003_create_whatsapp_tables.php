<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Códigos de 6 dígitos para vincular un número; solo se guarda el hash.
        Schema::create('codigos_vinculacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('codigo_hash');
            $table->unsignedTinyInteger('intentos')->default(0);
            $table->timestamp('expira_at');
            $table->timestamp('usado_at')->nullable();
            $table->timestamps();
        });

        // Bitácora de todo lo que entra y sale por WhatsApp (auditoría e idempotencia).
        Schema::create('mensajes_whatsapp', function (Blueprint $table) {
            $table->id();
            // Único: si Meta reenvía el mismo mensaje no se procesa dos veces. Nulo en los salientes.
            $table->string('wa_message_id')->nullable()->unique();
            $table->string('telefono', 20)->index();
            $table->string('direccion', 10);                   // entrante | saliente
            $table->jsonb('payload')->nullable();
            $table->string('estado', 12)->default('recibido'); // recibido | procesado | rechazado | enviado | fallido
            $table->unsignedSmallInteger('intentos')->default(0);
            $table->text('error')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensajes_whatsapp');
        Schema::dropIfExists('codigos_vinculacion');
    }
};
