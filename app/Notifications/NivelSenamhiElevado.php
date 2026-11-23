<?php

namespace App\Notifications;

use App\Models\LecturaSenamhi;
use Illuminate\Notifications\Notification;

/** SENAMHI elevó el nivel del aviso por lluvias en el distrito del ciudadano. */
class NivelSenamhiElevado extends Notification
{
    public function __construct(public LecturaSenamhi $lectura, public int $anterior) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'senamhi',
            'titulo' => "SENAMHI elevó el aviso por lluvias en {$this->lectura->distrito->nombre} a Nivel {$this->lectura->nivel}",
            'nivel' => $this->lectura->nivel,
            'anterior' => $this->anterior,
            'distrito' => $this->lectura->distrito->nombre,
            'fecha' => $this->lectura->fecha->toDateString(),
        ];
    }
}
