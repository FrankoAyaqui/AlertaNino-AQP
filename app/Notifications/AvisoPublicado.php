<?php

namespace App\Notifications;

use App\Models\Aviso;
use Illuminate\Notifications\Notification;

/** Se guarda en la bandeja del ciudadano cuando se publica un aviso para su distrito. */
class AvisoPublicado extends Notification
{
    public function __construct(public Aviso $aviso) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'aviso',
            'aviso_id' => $this->aviso->id,
            'titulo' => $this->aviso->titulo,
            'nivel' => $this->aviso->nivel,
            'fuente' => $this->aviso->fuente,
            'distrito' => $this->aviso->distrito?->nombre ?? 'Provincia de Arequipa',
            'vence_en' => $this->aviso->vence_en->toIso8601String(),
        ];
    }
}
