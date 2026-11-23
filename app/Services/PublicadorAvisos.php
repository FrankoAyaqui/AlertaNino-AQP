<?php

namespace App\Services;

use App\Models\Aviso;
use App\Models\User;
use App\Notifications\AvisoPublicado;
use Illuminate\Support\Facades\Notification;

/**
 * Notifica un aviso a los ciudadanos suscritos una sola vez.
 *
 * Un aviso de distrito llega a quienes eligieron ese distrito; uno de toda la
 * provincia llega a todos los que aceptaron recibir alertas.
 */
class PublicadorAvisos
{
    public function notificarSiCorresponde(Aviso $aviso): int
    {
        if (! $aviso->estaVigente() || $aviso->notificado_en !== null) {
            return 0;
        }

        $destinatarios = User::query()
            ->where('recibir_alertas', true)
            ->when($aviso->distrito_id, fn ($q) => $q->where('distrito_id', $aviso->distrito_id))
            ->get();

        Notification::send($destinatarios, new AvisoPublicado($aviso->loadMissing('distrito')));

        // forceFill: notificado_en no es asignable en masa desde formularios.
        $aviso->forceFill(['notificado_en' => now()])->save();

        return $destinatarios->count();
    }
}
