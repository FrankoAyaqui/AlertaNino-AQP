(()=>{
'use strict';
const el=id=>document.getElementById(id), node=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
let map,layer,response,busy=false;
try{map=L.map('impact-map').setView([-16.2,-72],7);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap contributors'}).addTo(map);el('impact-map-status').textContent='Puntos: INDECI / SINPAD. Mapa base: OpenStreetMap. Selecciona un punto para consultar los daños registrados.';}catch{el('impact-map-status').textContent='Mapa no disponible. Consulta la lista de afectaciones.';}
const state=r=>(r.status==='ok'?'Consulta completada':r.status==='stale'?'FUENTE SIN ACTUALIZAR':'FUENTE NO DISPONIBLE')+(r.checked_at?' · Última consulta exitosa: '+new Date(r.checked_at).toLocaleString('es-PE',{timeZone:'America/Lima'}):'');
async function get(source){const c=new AbortController(),timer=setTimeout(()=>c.abort(),25000);try{const r=await fetch('/api/seguimiento?source='+source,{signal:c.signal});if(!r.ok)throw Error();return await r.json();}finally{clearTimeout(timer);}}
function sourceLink(url,text){const a=node('a',text);const u=new URL(url);if(u.protocol==='https:'&&['portal.indeci.gob.pe','geosinpad.indeci.gob.pe'].includes(u.hostname)){a.href=u.href;a.target='_blank';a.rel='noopener noreferrer';}return a;}
function clearLayer(){if(layer){map.removeLayer(layer);layer=null;}}
function render(){
 const box=el('impact-list');box.replaceChildren();clearLayer();el('impact-summary').textContent='';if(!response)return;
 el('impact-status').textContent=state(response);
 if(!response.data){box.append(node('p','No se pudo verificar SINPAD. La falta de datos no significa ausencia de afectaciones.'));return;}
 const d=response.data;el('impact-status').textContent+=(d.latest_source_date?' · Fecha más reciente del catálogo consultado: '+d.latest_source_date:' · Sin fecha verificable en los registros recibidos');
 if(response.status!=='ok'){box.append(node('p','No se dibujan registros de una consulta que no pudo actualizarse. Consulta los reportes oficiales.'));return;}
 const period=el('impact-period').value,district=el('impact-district').value;
 const today=new Intl.DateTimeFormat('en-CA',{timeZone:'America/Lima',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
 const cutoff=period==='all'?'0000-00-00':new Date(Date.parse(today+'T12:00:00Z')-(Number(period)-1)*86400000).toISOString().slice(0,10);
 const features=d.features.filter(f=>f.properties.date>=cutoff&&(!district||f.properties.district===district));
 const districts=new Set(features.map(f=>f.properties.district));
 el('impact-summary').textContent=features.length+' eventos con daños registrados · '+districts.size+' distritos · '+(period==='all'?'HISTORIAL: no equivale a afectaciones activas hoy.':'Periodo: '+cutoff+' a '+today+'.');
 if(!features.length)box.append(node('p','La fuente consultada no aporta afectaciones para estos filtros. No significa que no existan. Puedes consultar el historial disponible y los reportes oficiales superiores.'));
 if(d.partial){const notice=node('p','Cobertura parcial: se consultaron los 300 eventos más recientes del servicio para estos fenómenos; no es un inventario completo.');notice.style.gridColumn='1 / -1';box.append(notice);}
 for(const f of features){const p=f.properties,card=node('article','');card.className='card';card.append(node('h3',p.district),node('p',p.province+' · '+p.hazard),node('p','Fecha del evento: '+p.date+' · SINPAD '+p.id));
 for(const [key,label] of [['SAFECTA','Personas afectadas'],['SDAMNI','Personas damnificadas'],['SAFECTAVIVI','Viviendas afectadas'],['SDESTRUVIVI','Viviendas destruidas'],['SINHABIVIVI','Viviendas inhabitables']])card.append(node('p',label+': '+(p.counts[key]??'Sin dato')));
 card.append(sourceLink(p.source,'Ver registro oficial'));if(!f.geometry)card.append(node('p','Sin coordenadas verificables; se conserva en la lista.'));box.append(card);}
 if(map&&features.length){layer=L.geoJSON({type:'FeatureCollection',features},{pointToLayer:(f,ll)=>L.circleMarker(ll,{radius:7,color:'#922f38',fillOpacity:.7}),onEachFeature:(f,l)=>{const p=f.properties,content=node('div','');content.append(node('strong',p.district),node('p',p.hazard+' · '+p.date),node('p','Personas afectadas: '+(p.counts.SAFECTA??'Sin dato')),sourceLink(p.source,'Registro oficial'));l.bindPopup(content);}}).addTo(map);if(layer.getBounds().isValid())map.fitBounds(layer.getBounds(),{maxZoom:11});}
}
async function impacts(){try{response=await get('impacts');const select=el('impact-district'),previous=select.value;select.replaceChildren(new Option('Todos los distritos',''));for(const name of [...new Set((response.data?.features||[]).map(f=>f.properties.district))].sort())select.add(new Option(name,name));if([...select.options].some(o=>o.value===previous))select.value=previous;render();}catch{response=null;clearLayer();el('impact-status').textContent='No se pudo consultar SINPAD. No se muestran datos de prueba.';el('impact-summary').textContent='';el('impact-list').replaceChildren();}}
async function reports(){const box=el('impact-reports');try{const r=await get('reports');box.replaceChildren();el('impact-reports-status').textContent=state(r);if(!r.data){box.append(node('p','No se pudo verificar el listado oficial de reportes.'));return;}
 const coverage=node('p',r.data.coverage);coverage.style.gridColumn='1 / -1';box.append(coverage);if(r.status!=='ok')box.append(node('p','Estos documentos proceden de la última consulta guardada, sin actualización verificada.'));
 if(!r.data.reports.length)box.append(node('p','No se encontraron reportes de eventos ocurridos en la página consultada. Los avisos de peligro inminente se excluyen.'));
 for(const p of r.data.reports){const card=node('article','');card.className='card';card.append(node('h4',p.zone),node('p',p.level+' · Fecha del reporte: '+p.date),node('p','Evento reportado por lluvias. Revisar el documento para conocer daños y fecha de ocurrencia. No confirma una emergencia activa hoy.'),sourceLink(p.url,'Abrir reporte oficial'));box.append(card);}
 }catch{box.replaceChildren();el('impact-reports-status').textContent='No se pudo verificar el listado de INDECI.';}}
async function refresh(){if(busy)return;busy=true;try{await Promise.allSettled([impacts(),reports()]);}finally{busy=false;}}
el('impact-period').addEventListener('change',render);el('impact-district').addEventListener('change',render);refresh();setInterval(()=>{if(!document.hidden)refresh();},300000);
})();
