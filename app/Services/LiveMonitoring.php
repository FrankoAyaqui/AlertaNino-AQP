<?php
namespace App\Services;
use Illuminate\Support\Facades\{Cache,Http};
use Carbon\CarbonImmutable;
use RuntimeException;
class LiveMonitoring {
 public const PLACES=['arequipa'=>[-16.3989,-71.535,'Arequipa'],'socabaya'=>[-16.468,-71.527,'Socabaya'],'miraflores'=>[-16.389,-71.506,'Miraflores'],'alto-selva-alegre'=>[-16.375,-71.508,'Alto Selva Alegre']];
 public function read(string $kind,string $place='arequipa'):array {
  $key='live-v1-'.$kind.'-'.$place.($kind==='warnings'?'-'.now()->toDateString():'');
  return Cache::remember($key,300,function()use($key,$kind,$place){
   try {$data=match($kind){'weather'=>$this->weather($place),'warnings'=>$this->warnings(),'impacts'=>app(AffectedAreas::class)->points(),'reports'=>app(AffectedAreas::class)->reports(),default=>throw new RuntimeException('Fuente inválida')};$result=['status'=>'ok','checked_at'=>now()->toIso8601String(),'data'=>$data];Cache::put($key.'-last',$result,now()->addDay());return $result;}
   catch(\Throwable $e){$last=Cache::get($key.'-last');return ['status'=>$last?'stale':'unavailable','checked_at'=>$last['checked_at']??null,'data'=>$last['data']??null,'message'=>'No se pudo verificar la fuente. No interpretar la falta de datos como ausencia de peligro.'];}
  });
 }
 private function json(string $url,array $params):array {
  $response=Http::connectTimeout(5)->timeout(18)->acceptJson()->get($url,$params)->throw();
  if(strlen($response->body())>12000000)throw new RuntimeException('Respuesta excesiva');
  $data=$response->json();if(!is_array($data)||isset($data['error']))throw new RuntimeException('Respuesta inválida');return $data;
 }
 private function weather(string $place):array {
  [$lat,$lon,$name]=self::PLACES[$place];
  $data=$this->json('https://api.open-meteo.com/v1/forecast',['latitude'=>$lat,'longitude'=>$lon,'current'=>'temperature_2m,relative_humidity_2m,precipitation,wind_speed_10m','daily'=>'temperature_2m_max,temperature_2m_min,precipitation_sum,precipitation_probability_max','forecast_days'=>3,'timezone'=>'America/Lima']);
  $current=$data['current']??[];
  if(!isset($current['time'])||!is_numeric($current['temperature_2m']??null))throw new RuntimeException('Sin clima válido');
  $at=CarbonImmutable::parse($current['time'],'America/Lima');
  if($at->lt(now()->subHours(3))||$at->gt(now()->addMinutes(30)))throw new RuntimeException('Clima fuera de fecha');
  return ['place'=>$name,'lat'=>$lat,'lon'=>$lon,'current'=>$current,'units'=>$data['current_units']??[],'daily'=>$data['daily']??[],'source'=>'https://open-meteo.com/','modelled'=>true];
 }
 private function warnings():array {
  $data=$this->json('https://idesep.senamhi.gob.pe/geoserver/g_acti_quebrada/wfs',['service'=>'WFS','version'=>'2.0.0','request'=>'GetFeature','typeNames'=>'view_av_activ_qdra','count'=>200,'outputFormat'=>'application/json','CQL_FILTER'=>"nombdep='AREQUIPA'"]);
  if(($data['type']??null)!=='FeatureCollection'||!is_array($data['features']??null))throw new RuntimeException('Capa inválida');
  $features=[];$undated=0;$historical=0;$latestDate=null;
  foreach($data['features'] as $f){$p=$f['properties']??[];if(strtoupper(trim($p['nombdep']??''))!=='AREQUIPA')continue;
   $date=substr((string)($p['fecha']??''),0,10);
   if(!preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)){$undated++;continue;}
   $latestDate=$latestDate===null||$date>$latestDate?$date:$latestDate;
   if($date!==now()->toDateString()){$historical++;continue;}
   $geometry=$f['geometry']??null;
   if(!in_array($geometry['type']??'', ['Polygon','MultiPolygon','Point','MultiPoint','LineString','MultiLineString']))$geometry=null;
   $features[]=['type'=>'Feature','geometry'=>$geometry,'properties'=>['id'=>(string)($f['id']??$p['gid']??''),'district'=>(string)($p['nombdist']??'Sin distrito'),'province'=>(string)($p['nombprov']??''),'date'=>$date,'level'=>(string)($p['nivel']??'Sin nivel'),'description'=>(string)($p['descripcion']??''),'recommendation'=>(string)($p['recomendacion']??'')]];
  }
  return ['type'=>'FeatureCollection','features'=>$features,'latest_source_date'=>$latestDate,'excluded_without_date'=>$undated,'excluded_other_date'=>$historical,'truncated'=>($data['totalFeatures']??$data['numberMatched']??0)>200,'source'=>'https://idesep.senamhi.gob.pe/portalidesep/wms.do'];
 }
}
