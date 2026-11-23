<?php

namespace App\Console\Commands;

use App\Services\SincronizadorOficial;
use Illuminate\Console\Command;

/** Lee SENAMHI, ENFEN e INDECI. Programado cada 30 minutos (routes/console.php). */
class SincronizarFuentes extends Command
{
    protected $signature = 'alerta:sincronizar';

    protected $description = 'Lee las fuentes oficiales (SENAMHI, ENFEN, INDECI) y actualiza avisos y niveles';

    public function handle(SincronizadorOficial $sincronizador): int
    {
        $resultados = $sincronizador->ejecutar();
        $this->table(['Fuente', 'Estado', 'Registros', 'Detalle'], collect($resultados)->map(fn ($s) => [
            $s->fuente, $s->estado, $s->registros, (string) $s->detalle,
        ])->all());

        // Falla solo si ninguna fuente respondió.
        return collect($resultados)->every(fn ($s) => $s->estado === 'error') ? self::FAILURE : self::SUCCESS;
    }
}
