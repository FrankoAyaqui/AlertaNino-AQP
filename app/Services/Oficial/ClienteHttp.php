<?php

namespace App\Services\Oficial;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

/** Cliente HTTP común para todas las fuentes oficiales. */
trait ClienteHttp
{
    protected function http(): PendingRequest
    {
        return Http::withHeaders([
            'User-Agent' => config('alerta.agente'),
            'Accept-Language' => 'es-PE,es',
        ])
            ->connectTimeout(10)
            ->timeout(config('alerta.timeout'));
    }
}
