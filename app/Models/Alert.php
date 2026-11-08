<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Alert extends Model { protected $fillable=["title", "zone_id", "level", "message", "source_url", "issued_at", "expires_at", "status"]; public function zone(){return $this->belongsTo(Zone::class);} protected function casts():array{return ['issued_at'=>'datetime','expires_at'=>'datetime'];}
}
