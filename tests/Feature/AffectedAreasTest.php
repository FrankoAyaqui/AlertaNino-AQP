<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Support\Facades\{Http,Cache};
use App\Services\AffectedAreas;
class AffectedAreasTest extends TestCase {
 protected function setUp():void {parent::setUp();config(['cache.default'=>'array']);Cache::flush();Http::preventStrayRequests();$this->travelTo(now()->setDate(2026,11,8));}
 public function test_only_actual_positive_impacts_are_listed_and_geometry_is_validated():void {
 $f=fn($id,$count,$date,$dep='AREQUIPA',$x=-71.5)=>['attributes'=>['IDE_SINPAD'=>$id,'FECHA'=>strtotime($date)*1000,'DEPARTAMENTO'=>$dep,'FENOMENO'=>'LLUVIAS INTENSAS','DISTRITO'=>'SOCABAYA','SAFECTA'=>$count],'geometry'=>['x'=>$x,'y'=>-16.4]];
 Http::fake(['*'=>Http::response(['features'=>[$f(1,12,'2024-01-01'),$f(1,12,'2024-01-01'),$f(2,0,'2026-11-08'),$f(3,8,'2026-11-09'),$f(4,9,'2026-11-07','PUNO'),$f(5,2,'2026-11-07','AREQUIPA',500)],'exceededTransferLimit'=>true])]);
 $this->getJson('/api/seguimiento?source=impacts')->assertOk()->assertJsonPath('status','ok')->assertJsonCount(2,'data.features')->assertJsonPath('data.features.0.properties.date','2024-01-01')->assertJsonPath('data.features.0.properties.counts.SDAMNI',null)->assertJsonPath('data.features.1.geometry',null)->assertJsonPath('data.partial',true);
 }
 public function test_reports_reject_preventive_notices_foreign_links_and_invalid_dates():void {
 $link=fn($path,$text)=>'<a href="https://portal.indeci.gob.pe/emergencias/'.$path.'/">'.$text.'</a>';
 $good='REPORTE COMPLEMENTARIO N. 1 - 7/11/2026 LLUVIAS INTENSAS EN EL DISTRITO DE OCOÑA – AREQUIPA';
 $html=$link('real',$good).$link('real',$good).$link('riesgo','REPORTE DE PELIGRO INMINENTE 8/11/2026 LLUVIAS INTENSAS EN EL DISTRITO DE OCOÑA – AREQUIPA').$link('bad',str_replace('7/11/2026','31/2/2026',$good)).str_replace('portal.indeci.gob.pe','evil.test',$link('external',$good));
 Http::fake(['*'=>Http::response($html)]);
 $this->getJson('/api/seguimiento?source=reports')->assertJsonPath('status','ok')->assertJsonCount(1,'data.reports')->assertJsonPath('data.reports.0.zone','OCOÑA')->assertJsonPath('data.reports.0.date','2026-11-07');
 }
 public function test_source_failures_remain_explicit_and_keep_last_good():void {
 Http::fake(['*'=>Http::sequence()->push(['features'=>[]])->push([],503)]);
 $this->getJson('/api/seguimiento?source=impacts')->assertJsonPath('status','ok');Cache::forget('live-v1-impacts-arequipa');
 $this->getJson('/api/seguimiento?source=impacts')->assertJsonPath('status','stale');
 }
 public function test_changed_portal_format_is_unavailable_not_no_damage():void {
 Http::fake(['*'=>Http::response('<html>Maintenance</html>')]);
 $this->getJson('/api/seguimiento?source=reports')->assertJsonPath('status','unavailable')->assertJsonPath('data',null);
 }
}
