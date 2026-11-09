(()=>{
'use strict';
const el=id=>document.getElementById(id), node=(tag,text)=>{const n=document.createElement(tag);n.textContent=text;return n;};
const interval=300000;
let map,layer,response,busy=false,nextAt=Date.now(),firstView=true,selected=null;
const markers=new Map();
try{map=L.map('impact-map').setView([-16.2,-72],7);L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',{maxZoom:18,attribution:'© OpenStreetMap contributors'}).addTo(map);el('impact-map-status').textContent='Selecciona un punto para consultar el evento. También puedes localizarlo desde la lista. La actualización automática conserva el encuadre del mapa.';}catch{el('impact-map-status').textContent='Mapa no disponible. Consulta la lista de afectaciones.';el('impact-fit').disabled=true;el('impact-fullscreen').disabled=true;}
const state=r=>(r.status==='ok'?'Consulta completada':r.status==='stale'?'FUENTE SIN ACTUALIZAR':'FUENTE NO DISPONIBLE')+(r.checked_at?' · Última consulta exitosa: '+new Date(r.checked_at).toLocaleString('es-PE',{timeZone:'America/Lima'}):'');
async function get(source){const c=new AbortController(),timer=setTimeout(()=>c.abort(),25000);try{const r=await fetch('/api/seguimiento?source='+source,{signal:c.signal});if(!r.ok)throw Error();return await r.json();}finally{clearTimeout(timer);}}
function sourceLink(url,text){const a=node('a',text);try{const u=new URL(url);if(u.protocol==='https:'&&['portal.indeci.gob.pe','geosinpad.indeci.gob.pe'].includes(u.hostname)){a.href=u.href;a.target='_blank';a.rel='noopener noreferrer';}}catch{}return a;}
function clearLayer(){if(layer){map.removeLayer(layer);layer=null;}markers.clear();}
function fit(){if(layer&&layer.getBounds().isValid())map.fitBounds(layer.getBounds(),{maxZoom:11,animate:false});}
function pick(id,scroll){selected=id;for(const card of el('impact-list').querySelectorAll('article'))card.classList.toggle('impact-selected',card.dataset.event===id);const marker=markers.get(id);if(marker){map.setView(marker.getLatLng(),Math.max(map.getZoom(),11),{animate:false});marker.openPopup();if(scroll)el('impact-map').scrollIntoView({behavior:'smooth',block:'center'});}}
function cutoff(days,today){return new Date(Date.parse(today+'T12:00:00Z')-(days-1)*86400000).toISOString().slice(0,10);}
function render(adjustView=false){
 const box=el('impact-list');box.replaceChildren();clearLayer();el('impact-summary').textContent='';if(!response)return;
 el('impact-status').textContent=state(response);
 if(!response.data){box.append(node('p','No se pudo verificar SINPAD. La falta de datos no significa ausencia de afectaciones.'));return;}
 const d=response.data;el('impact-status').textContent+=(d.latest_source_date?' · Fecha más reciente del catálogo consultado: '+d.latest_source_date:' · Sin fecha verificable en los registros recibidos');
 if(response.status!=='ok'){box.append(node('p','No se dibujan registros de una consulta que no pudo actualizarse. Consulta los reportes oficiales.'));return;}
 const period=el('impact-period').value,district=el('impact-district').value,hazard=el('impact-hazard').value;
 const today=response.server_date||new Intl.DateTimeFormat('en-CA',{timeZone:'America/Lima',year:'numeric',month:'2-digit',day:'2-digit'}).format(new Date());
 const since=period==='all'?'0000-00-00':cutoff(Number(period),today),recent=cutoff(30,today);
 const features=d.features.filter(f=>f.properties.date>=since&&f.properties.date<=today&&(!district||f.properties.district===district)&&(!hazard||f.properties.hazard===hazard));
 const districts=new Set(features.map(f=>f.properties.district));
 el('impact-summary').textContent=features.length+' eventos con daños registrados · '+districts.size+' distritos · '+(period==='all'?'HISTORIAL: no equivale a afectaciones activas hoy.':'Periodo: '+since+' a '+today+'.');
 if(d.latest_source_date&&d.latest_source_date<recent){const notice=node('p','La fuente cartográfica no aporta eventos recientes: su último registro recibido es del '+d.latest_source_date+'. La consulta automática no acredita daños actuales.');notice.className='errors';notice.style.gridColumn='1 / -1';box.append(notice);}
 if(!features.length)box.append(node('p','No hay registros disponibles para estos filtros. No significa ausencia de afectaciones. Consulta el historial o los reportes oficiales.'));
 if(d.partial){const notice=node('p','Cobertura parcial: últimos 300 eventos del servicio para estos fenómenos; no es un inventario completo.');notice.style.gridColumn='1 / -1';box.append(notice);}
 for(const f of features){const p=f.properties,card=node('article','');card.className='card';card.dataset.event=p.id;card.classList.toggle('impact-selected',selected===p.id);card.append(node('h3',p.district),node('p',p.province+' · '+p.hazard),node('p','Fecha del evento: '+p.date+' · SINPAD '+p.id));
 for(const [key,label] of [['SAFECTA','Personas afectadas'],['SDAMNI','Personas damnificadas'],['SAFECTAVIVI','Viviendas afectadas'],['SDESTRUVIVI','Viviendas destruidas'],['SINHABIVIVI','Viviendas inhabitables']])card.append(node('p',label+': '+(p.counts[key]??'Sin dato')));
 card.append(sourceLink(p.source,'Ver registro oficial'));if(f.geometry&&map){const button=node('button','Ver en mapa');button.type='button';button.className='secondary';button.addEventListener('click',()=>pick(p.id,true));card.append(button);}else if(!f.geometry)card.append(node('p','Sin coordenadas verificables; se conserva en la lista.'));box.append(card);}
 if(map&&features.length){layer=L.geoJSON({type:'FeatureCollection',features},{pointToLayer:(f,ll)=>L.circleMarker(ll,{radius:7,color:f.properties.date>=recent?'#922f38':'#596575',fillOpacity:.7}),onEachFeature:(f,l)=>{const p=f.properties,content=node('div','');content.append(node('strong',p.district),node('p',p.hazard+' · '+p.date),node('p','Personas afectadas: '+(p.counts.SAFECTA??'Sin dato')),node('p','Atribución a El Niño no confirmada en este registro.'),sourceLink(p.source,'Registro oficial'));l.bindPopup(content);markers.set(p.id,l);l.on('click',()=>{selected=p.id;for(const card of box.querySelectorAll('article'))card.classList.toggle('impact-selected',card.dataset.event===p.id);});}}).addTo(map);if(adjustView||firstView){fit();firstView=false;}if(selected&&markers.has(selected))markers.get(selected).openPopup();}
}
function options(id,names){const select=el(id),previous=select.value;const label=select.options[0].text;select.replaceChildren(new Option(label,''));for(const name of [...new Set(names)].sort())select.add(new Option(name,name));if([...select.options].some(o=>o.value===previous))select.value=previous;}
async function impacts(){try{response=await get('impacts');const fs=response.data?.features||[];options('impact-district',fs.map(f=>f.properties.district));options('impact-hazard',fs.map(f=>f.properties.hazard));render();}catch{response=null;clearLayer();el('impact-status').textContent='No se pudo consultar SINPAD. No se muestran datos de prueba.';el('impact-summary').textContent='';el('impact-list').replaceChildren();}}
async function reports(){const box=el('impact-reports');try{const r=await get('reports');box.replaceChildren();el('impact-reports-status').textContent=state(r);if(!r.data){box.append(node('p','No se pudo verificar el listado oficial de reportes.'));return;}
 const coverage=node('p',r.data.coverage);coverage.style.gridColumn='1 / -1';box.append(coverage);if(r.status!=='ok')box.append(node('p','Estos documentos proceden de la última consulta guardada, sin actualización verificada.'));
 if(!r.data.reports.length)box.append(node('p','No se encontraron reportes de eventos ocurridos en la página consultada. Los avisos de peligro inminente se excluyen.'));
 for(const p of r.data.reports){const card=node('article','');card.className='card';card.append(node('h4',p.zone),node('p',p.level+' · Fecha del reporte: '+p.date),node('p','Evento reportado por lluvias. Revisar el documento para conocer daños y fecha de ocurrencia. No confirma una emergencia activa hoy.'),sourceLink(p.url,'Abrir reporte oficial'));box.append(card);}
 }catch{box.replaceChildren();el('impact-reports-status').textContent='No se pudo verificar el listado de INDECI.';}}

function countdown(){let message;if(busy)message='Actualizando consulta…';else if(!navigator.onLine)message='Sin conexión. Se volverá a consultar al recuperar Internet.';else if(!el('impact-auto').checked)message='Actualización automática pausada. Puedes actualizar manualmente.';else if(document.hidden)message='Se actualizará al volver a la página si corresponde.';else {const seconds=Math.max(0,Math.ceil((nextAt-Date.now())/1000));message='Próxima consulta en '+Math.floor(seconds/60)+':'+String(seconds%60).padStart(2,'0')+' · Caché del servidor: hasta 5 minutos.';}el('impact-next').textContent=message;}
async function refresh(){if(busy)return;busy=true;el('impact-refresh').disabled=true;countdown();try{await Promise.allSettled([impacts(),reports()]);}finally{busy=false;nextAt=Date.now()+interval;el('impact-refresh').disabled=false;countdown();}}
for(const id of ['impact-period','impact-district','impact-hazard'])el(id).addEventListener('change',()=>render(true));
el('impact-refresh').addEventListener('click',refresh);el('impact-fit').addEventListener('click',fit);
el('impact-fullscreen').addEventListener('click',async()=>{try{const frame=el('impact-map-frame');if(document.fullscreenElement===frame)await document.exitFullscreen();else await frame.requestFullscreen();}catch{el('impact-map-status').textContent='El navegador no permite pantalla completa; puedes usar zoom y desplazamiento en el mapa.';}});
document.addEventListener('fullscreenchange',()=>{el('impact-fullscreen').textContent=document.fullscreenElement?'Salir de pantalla completa':'Ampliar mapa';setTimeout(()=>map?.invalidateSize(),100);});
function due(){if(el('impact-auto').checked&&!document.hidden&&navigator.onLine&&Date.now()>=nextAt)refresh();countdown();}
el('impact-auto').addEventListener('change',due);document.addEventListener('visibilitychange',due);window.addEventListener('online',()=>{if(el('impact-auto').checked)refresh();});window.addEventListener('offline',countdown);
refresh();setInterval(due,1000);
})();
