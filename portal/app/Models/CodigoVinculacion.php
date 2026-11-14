<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class CodigoVinculacion extends Model
{
    protected $table = 'codigos_vinculacion';

    public const VIGENCIA_HORAS = 24;

    public const MAX_INTENTOS = 5;

    protected $fillable = ['user_id', 'codigo_hash', 'intentos', 'expira_at', 'usado_at'];

    protected function casts(): array
    {
        return ['expira_at' => 'datetime', 'usado_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Genera un código nuevo y lo devuelve en claro (única vez que existe así).
     * Invalida los códigos anteriores de esa persona.
     */
    public static function generarPara(User $usuario): string
    {
        self::where('user_id', $usuario->id)->whereNull('usado_at')->delete();

        $codigo = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        self::create([
            'user_id' => $usuario->id,
            'codigo_hash' => Hash::make($codigo),
            'expira_at' => now()->addHours(self::VIGENCIA_HORAS),
        ]);

        return $codigo;
    }

    /** Código activo (sin usar, vigente y con intentos disponibles) de una persona. */
    public static function activoDe(User $usuario): ?self
    {
        return self::where('user_id', $usuario->id)
            ->whereNull('usado_at')
            ->where('expira_at', '>', now())
            ->where('intentos', '<', self::MAX_INTENTOS)
            ->latest('id')
            ->first();
    }

    public function coincide(string $codigo): bool
    {
        return Hash::check($codigo, $this->codigo_hash);
    }
}
