<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\{Http,Cache};
use App\Services\LiveMonitoring;
class MonitoringTest extends TestCase {
 protected function setUp():void{parent::setUp();config(['cache.default'=>'array']);Cache::flush();Http::preventStrayRequests();}
 public function test_home_is_real_monitoring_without_fixture_alerts():void{$this->get('/')->assertOk()->assertSee('Tiempo y pronóstico local')->assertSee('SENAMHI')->assertDontSee('Ejercicio de preparación');}
 public function test_weather_uses_source_and_keeps_last_good_on_failure():void{
 Http::fake(['api.open-meteo.com/*'=>Http::sequence()->push(['current'=>['time'=>now()->format('Y-m-d\TH:i'),'temperature_2m'=>18],'daily'=>[]])->push([],503)]);
 $this->getJson('/api/seguimiento?source=weather&place=socabaya')->assertOk()->assertJsonPath('status','ok')->assertJsonPath('data.place','Socabaya');
 Cache::forget('live-v1-weather-socabaya');
 $this->getJson('/api/seguimiento?source=weather&place=socabaya')->assertJsonPath('status','stale')->assertJsonPath('data.current.temperature_2m',18);
 }
 public function test_wrong_date_and_failures_do_not_become_live_data():void{
 Http::fake(['*'=>Http::response(['current'=>['time'=>now()->subDays(2)->format('Y-m-d\TH:i'),'temperature_2m'=>18]])]);
 $this->getJson('/api/seguimiento?source=weather')->assertJsonPath('status','unavailable')->assertJsonPath('data',null);
 $this->getJson('/api/seguimiento?source=unknown')->assertUnprocessable();
 $this->getJson('/api/seguimiento?source=weather&place=https://evil.test')->assertUnprocessable();
 }
 public function test_warning_filter_uses_today_and_arequipa_only():void{
 $f=fn($date,$dep)=>['type'=>'Feature','properties'=>['fecha'=>$date,'nombdep'=>$dep,'nombdist'=>'SOCABAYA','nivel'=>'Nivel 1'],'geometry'=>null];
 Http::fake(['*'=>Http::response(['type'=>'FeatureCollection','features'=>[$f(now()->toDateString().'Z','AREQUIPA'),$f(now()->subDay()->toDateString(),'AREQUIPA'),$f('', 'AREQUIPA'),$f(now()->toDateString(),'PUNO')]])]);
 $this->getJson('/api/seguimiento?source=warnings')->assertOk()->assertJsonCount(1,'data.features')->assertJsonPath('data.excluded_other_date',1)->assertJsonPath('data.excluded_without_date',1);
 }
}
