<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MensajeWhatsapp extends Model
{
    protected $table = 'mensajes_whatsapp';

    protected $fillable = ['wa_message_id', 'telefono', 'direccion', 'payload', 'estado', 'intentos', 'error'];

    protected function casts(): array
    {
        return ['payload' => 'array'];
    }

    /** Texto del mensaje entrante o saliente. */
    public function texto(): ?string
    {
        return $this->payload['text']['body'] ?? $this->payload['texto'] ?? null;
    }
}
