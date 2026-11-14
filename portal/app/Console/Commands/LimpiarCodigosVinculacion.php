<?php

namespace App\Console\Commands;

use App\Models\CodigoVinculacion;
use Illuminate\Console\Command;

class LimpiarCodigosVinculacion extends Command
{
    protected $signature = 'codigos:limpiar';

    protected $description = 'Borra los códigos de vinculación vencidos o ya usados.';

    public function handle(): int
    {
        $borrados = CodigoVinculacion::where('expira_at', '<', now())->orWhereNotNull('usado_at')->delete();

        $this->info("{$borrados} código(s) eliminados.");

        return self::SUCCESS;
    }
}
