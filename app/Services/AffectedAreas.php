<?php
namespace App\Services;

use Carbon\CarbonImmutable;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/** Official reported events, never inferred from forecast thresholds. */
class AffectedAreas
{
    public const MAP = 'https://geosinpad.indeci.gob.pe/indeci/rest/services/Emergencias/EMERGENCIAS_SINPAD/MapServer/0';
    public const PORTAL = 'https://portal.indeci.gob.pe/emergencias/';
    private const HAZARDS = ['LLUVIAS INTENSAS','INUNDACION','INUNDACIÓN','HUAICO','HUAYCO','DESLIZAMIENTO','ALUVION','ALUVIÓN','SEQUIA','SEQUÍA'];

    public function points(): array
    {
        $hazards = implode("','", self::HAZARDS);
        $data = Http::connectTimeout(5)->timeout(18)->get(self::MAP.'/query', [
            'f'=>'json', 'where'=>"DEPARTAMENTO='AREQUIPA' AND FECHA IS NOT NULL AND FENOMENO IN ('$hazards')",
            'outFields'=>'IDE_SINPAD,FECHA,DEPARTAMENTO,PROVINCIA,DISTRITO,FENOMENO,SAFECTA,SDAMNI,SAFECTAVIVI,SDESTRUVIVI,SINHABIVIVI',
            'orderByFields'=>'FECHA DESC,IDE_SINPAD DESC','resultRecordCount'=>300,'outSR'=>4326,'returnGeometry'=>'true',
        ])->throw()->json();
        if (!is_array($data['features']??null) || isset($data['error'])) throw new RuntimeException('SINPAD no disponible');
        $features=[]; $seen=[]; $latest=null;
        foreach ($data['features'] as $f) {
            $p=$f['attributes']??[];
            if (($p['DEPARTAMENTO']??'')!=='AREQUIPA' || !in_array($p['FENOMENO']??'',self::HAZARDS,true) || !is_numeric($p['FECHA']??null)) continue;
            $date=CarbonImmutable::createFromTimestampUTC($p['FECHA']/1000)->setTimezone('America/Lima')->toDateString();
            if ($date>now()->toDateString() || $date<'1970-01-01') continue;
            $latest=max($latest??$date,$date);
            $counts=[];
            foreach (['SAFECTA','SDAMNI','SAFECTAVIVI','SDESTRUVIVI','SINHABIVIVI'] as $k) $counts[$k]=is_numeric($p[$k]??null)&&$p[$k]>=0?(int)$p[$k]:null;
            // An event without a positive reported impact is not an affected zone.
            if (!array_filter($counts,fn($v)=>$v!==null&&$v>0)) continue;
            $id=(string)($p['IDE_SINPAD']??''); if ($id===''||isset($seen[$id])) continue; $seen[$id]=true;
            $x=$f['geometry']['x']??null; $y=$f['geometry']['y']??null;
            $valid=is_numeric($x)&&is_numeric($y)&&$x>=-82&&$x<=-68&&$y>=-19&&$y<=0;
            $features[]=['type'=>'Feature','geometry'=>$valid?['type'=>'Point','coordinates'=>[(float)$x,(float)$y]]:null,
                'properties'=>['id'=>$id,'district'=>(string)($p['DISTRITO']??'Sin distrito'),'province'=>(string)($p['PROVINCIA']??''),
                    'hazard'=>$p['FENOMENO'],'date'=>$date,'counts'=>$counts,'source'=>self::MAP.'/query?f=pjson&where=IDE_SINPAD%3D'.rawurlencode($id).'&outFields=*&returnGeometry=true']];
        }
        return ['features'=>$features,'latest_source_date'=>$latest,'partial'=>(bool)($data['exceededTransferLimit']??false),'source'=>self::MAP];
    }

    public function reports(): array
    {
        // This listing includes imminent-danger notices: explicitly reject them.
        $html=Http::connectTimeout(5)->timeout(18)->get(self::PORTAL,['title'=>'AREQUIPA','tipo_alerta'=>'lluvias-intensas'])->throw()->body();
        if (strlen($html)>5000000) throw new RuntimeException('Listado excesivo');
        return $this->parseReports($html);
    }

    public function parseReports(string $html): array
    {
        $dom=new DOMDocument(); $old=libxml_use_internal_errors(true);
        try {$dom->loadHTML('<?xml encoding="UTF-8">'.$html,LIBXML_NONET);}
        finally {libxml_clear_errors();libxml_use_internal_errors($old);}
        $rows=[]; $recognized=false;
        foreach ((new DOMXPath($dom))->query('//a[@href]') as $a) {
            $title=preg_replace('/\s+/u',' ',trim($a->textContent));
            if (str_contains($title,'REPORTE')||str_contains($title,'INFORME DE EMERGENCIA')) $recognized=true;
            if (!preg_match('/^(REPORTE (COMPLEMENTARIO|PRELIMINAR)|INFORME DE EMERGENCIA)/u',$title) || !str_contains($title,'AREQUIPA') || !str_contains($title,'LLUVIAS INTENSAS')) continue;
            $url=$a->getAttribute('href');
            if (parse_url($url,PHP_URL_SCHEME)!=='https'||parse_url($url,PHP_URL_HOST)!=='portal.indeci.gob.pe'||!str_starts_with(parse_url($url,PHP_URL_PATH)??'','/emergencias/')) continue;
            if (!preg_match('~(\d{1,2})/(\d{1,2})/(\d{4})~',$title,$m)||!checkdate((int)$m[2],(int)$m[1],(int)$m[3])) continue;
            $date=sprintf('%04d-%02d-%02d',$m[3],$m[2],$m[1]); if($date>now()->toDateString()) continue;
            $zone='Consultar ubicación en el reporte';$level='Sin detalle territorial';
            if(preg_match('/EN (?:EL DISTRITO|LA PROVINCIA) DE (.+?)\s*[–—-]\s*AREQUIPA/u',$title,$m)) {$zone=trim($m[1]);$level=str_contains($title,'EN EL DISTRITO')?'Distrito':'Provincia';}
            $rows[$url]=['title'=>$title,'date'=>$date,'zone'=>$zone,'level'=>$level,'url'=>$url];
        }
        if (!$recognized) throw new RuntimeException('No se reconoce el listado oficial');
        $rows=array_values($rows);usort($rows,fn($a,$b)=>strcmp($b['date'],$a['date']));
        return ['reports'=>$rows,'source'=>self::PORTAL.'?title=AREQUIPA&tipo_alerta=lluvias-intensas','coverage'=>'Última página del listado oficial de lluvias intensas para Arequipa; no es un inventario completo. La fecha corresponde al reporte, no necesariamente al inicio del evento.'];
    }
}
