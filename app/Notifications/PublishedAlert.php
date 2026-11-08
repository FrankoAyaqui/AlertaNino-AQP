<?php
namespace App\Notifications;
use Illuminate\Notifications\Notification;
use App\Models\Alert;
class PublishedAlert extends Notification {
 public function __construct(public Alert $alert){}
 public function via(object $notifiable):array{return ['database'];}
 public function toArray(object $notifiable):array{return ['alert_id'=>$this->alert->id,'title'=>$this->alert->title,'zone'=>$this->alert->zone->name,'demo'=>true];}
}
