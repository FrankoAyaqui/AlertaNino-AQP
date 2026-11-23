<?php

namespace App\Notifications;

use App\Models\ComunicadoEnfen;
use Illuminate\Notifications\Notification;

/** ENFEN cambió el estado del sistema de alerta ante El Niño. */
class EstadoEnfenCambio extends Notification
{
    public function __construct(public ComunicadoEnfen $comunicado, public ?string $anterior) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'tipo' => 'enfen',
            'titulo' => "ENFEN: {$this->comunicado->estado} (comunicado N.º {$this->comunicado->numero})",
            'anterior' => $this->anterior,
            'pdf_url' => $this->comunicado->pdf_url,
        ];
    }
}
