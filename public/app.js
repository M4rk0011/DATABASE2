'use strict';
const app=JSON.parse(document.getElementById('app-data').textContent);
const q=(s)=>document.querySelector(s);
function navigate(page,id){const u=new URL('index.php',location.href);u.searchParams.set('page',page);if(id)u.searchParams.set('id',id);location.href=u;}
function postAction(action,id='',value='',extra={}){const form=document.createElement('form');form.method='post';for(const [name,v]of Object.entries({action,id,value,csrf:app.csrf,...extra})){const input=document.createElement('input');input.type='hidden';input.name=name;input.value=v;form.append(input);}document.body.append(form);form.submit();}
document.addEventListener('click',e=>{
 const b=e.target.closest('[data-href],[data-action],[data-password],[data-toggle],[data-location],[data-witness-add],[data-witness-remove],[data-print],[data-share]');if(!b||b.disabled)return;
 e.preventDefault();
 if(b.dataset.href){location.href=b.dataset.href;return;}
 if(b.dataset.password){const i=document.getElementById(b.dataset.password);i.type=i.type==='password'?'text':'password';b.textContent=i.type==='password'?'Show':'Hide';return;}
 if(b.dataset.toggle){const p=document.getElementById(b.dataset.toggle);p.hidden=!p.hidden;return;}
 if(b.dataset.location){const names={'Main Library':'University of Baguio Main Library','Gymnasium':'University Gymnasium','Parking Lot':'Faculty Parking Lot','Administration':'Administration Building','Cafeteria':'University Cafeteria'};q('#address').value=names[b.dataset.location]||b.dataset.location;return;}
 if(b.hasAttribute('data-witness-add')){addWitness();return;}
 if(b.hasAttribute('data-witness-remove')){b.closest('.witness-item').remove();return;}
 if(b.hasAttribute('data-print')){window.print();return;}
 if(b.hasAttribute('data-share')){const title=q('.concern-header h1')?.textContent||'Concern report';location.href='mailto:?subject='+encodeURIComponent(title)+'&body='+encodeURIComponent('View the report (login required): '+location.href);return;}
 if(b.dataset.action){if(b.dataset.action.startsWith('delete-')&&!confirm('Are you sure you want to delete this record?'))return;
  let extra={};if(b.dataset.action==='save-user'){const panel=b.closest('.user-edit-form');for(const i of panel.querySelectorAll('[name]'))extra[i.name]=i.value;}
  postAction(b.dataset.action,b.dataset.id,b.dataset.value,extra);
 }
});
function addWitness(){const i=q('[name=witnessInput]');if(!i?.value.trim())return;const row=document.createElement('div');row.className='witness-item';const span=document.createElement('span');span.textContent=i.value.trim();const input=document.createElement('input');input.type='hidden';input.name='witnesses[]';input.value=i.value.trim();const button=document.createElement('button');button.type='button';button.className='btn-remove';button.dataset.witnessRemove='true';button.textContent='×';row.append(span,input,button);q('#witnesses-list').append(row);i.value='';}
q('[name=witnessInput]')?.addEventListener('keydown',e=>{if(e.key==='Enter'){e.preventDefault();addWitness();}});
function anonymousFields(){const checkbox=q('[name=isAnonymous]');const panel=q('#contact-fields');if(!checkbox||!panel)return;panel.hidden=checkbox.checked;for(const i of panel.querySelectorAll('input')){i.disabled=checkbox.checked;}}
q('[name=isAnonymous]')?.addEventListener('change',anonymousFields);anonymousFields();
let timer;
function filterList(){const u=new URL(location.href);for(const i of document.querySelectorAll('[data-filter]'))u.searchParams.set(i.name,i.value);location.href=u;}
for(const i of document.querySelectorAll('[data-filter]'))i.addEventListener(i.tagName==='SELECT'?'change':'input',()=>{clearTimeout(timer);timer=setTimeout(filterList,i.tagName==='SELECT'?0:650);});
for(const i of document.querySelectorAll('[data-status-change]'))i.addEventListener('change',()=>postAction('status',i.dataset.id,i.value));
const mapElement=q('#report-map')||q('#map');
if(mapElement){
 if(typeof L==='undefined'){mapElement.textContent='Map could not load. Check your internet connection and reload.';}
 else {
 const bounds=L.latLngBounds([16.414543,120.595925],[16.416568,120.599075]);
 const map=L.map(mapElement,{minZoom:16,maxZoom:19,center:[16.4155555556,120.5975],zoom:17,maxBounds:bounds,maxBoundsViscosity:0.85});
 // A local coordinate grid keeps the map usable when every imagery provider is offline.
 const Grid=L.GridLayer.extend({createTile(coords){const canvas=document.createElement('canvas');canvas.width=canvas.height=256;const c=canvas.getContext('2d');c.fillStyle='#f3f6f8';c.fillRect(0,0,256,256);c.strokeStyle='#d8e0e7';c.lineWidth=1;for(let x=0;x<=256;x+=64){c.beginPath();c.moveTo(x,0);c.lineTo(x,256);c.stroke();c.beginPath();c.moveTo(0,x);c.lineTo(256,x);c.stroke();}const ll=map.unproject([coords.x*256,coords.y*256],coords.z);c.fillStyle='#617185';c.font='11px sans-serif';c.fillText(ll.lat.toFixed(5)+' N',8,18);c.fillText(ll.lng.toFixed(5)+' E',8,34);return canvas;}});
 const toolbar=document.createElement('div');toolbar.className='map-toolbar';
 const select=document.createElement('select');select.setAttribute('aria-label','Map background');
 for(const [value,label]of [['street','Street map'],['alternate','Alternate street map'],['satellite','Satellite'],['offline','Offline coordinate grid']]){const o=document.createElement('option');o.value=value;o.textContent=label;select.append(o);}
 const reset=document.createElement('button');reset.type='button';reset.className='btn btn-outline';reset.textContent='Center campus';reset.onclick=()=>map.fitBounds(bounds,{padding:[24,24]});
 const retry=document.createElement('button');retry.type='button';retry.className='btn btn-outline';retry.textContent='Retry online map';
 const notice=document.createElement('span');notice.className='map-status';notice.setAttribute('role','status');toolbar.append(select,reset,retry,notice);mapElement.before(toolbar);
 const sources={street:['tile.php?provider=street&z={z}&x={x}&y={y}','© OpenStreetMap contributors'],alternate:['tile.php?provider=alternate&z={z}&x={x}&y={y}','© OpenStreetMap contributors © CARTO'],satellite:['tile.php?provider=satellite&z={z}&x={x}&y={y}','Tiles © Esri — Source: Esri, Maxar, Earthstar Geographics and the GIS User Community']};
 let currentLayer,tileTimer,layerVersion=0;
 function background(kind,automatic=false){const version=++layerVersion;clearTimeout(tileTimer);if(currentLayer)map.removeLayer(currentLayer);select.value=kind;mapElement.dataset.background=kind;
  if(kind==='offline'){currentLayer=new Grid({attribution:'Coordinate grid only — not aerial imagery',maxZoom:19}).addTo(map);notice.textContent=automatic?'Online imagery unavailable. Offline grid active; pins and location selection still work.':'Offline coordinate grid — pins use real latitude/longitude.';return;}
  notice.textContent='Loading map background…';let loaded=0,errors=0;const source=sources[kind];
  const fallback=()=>{if(version!==layerVersion||loaded)return;background(kind==='street'?'alternate':'offline',true);};
  currentLayer=L.tileLayer(source[0],{attribution:source[1],maxZoom:19});
  currentLayer.on('tileload',()=>{if(version!==layerVersion)return;loaded++;clearTimeout(tileTimer);notice.textContent=kind==='satellite'?'Satellite imagery':'Street map';});
  currentLayer.on('tileerror',()=>{if(version!==layerVersion)return;if(++errors>=3&&!loaded){clearTimeout(tileTimer);tileTimer=setTimeout(fallback,800);}});
  currentLayer.addTo(map);tileTimer=setTimeout(fallback,15000);
 }
 select.onchange=()=>background(select.value);retry.onclick=()=>background('street');background('street');
 L.rectangle(bounds,{color:'#dc3545',weight:2,opacity:.95,fillOpacity:.06}).addTo(map);
 if(mapElement.id==='report-map'){
  const lat=q('[name=latitude]'),lng=q('[name=longitude]');let initial=L.latLng(Number(lat.value)||16.4155555556,Number(lng.value)||120.5975);if(!bounds.contains(initial))initial=bounds.getCenter();const marker=L.marker(initial,{draggable:true}).addTo(map);
  function setPoint(p){if(!bounds.contains(p)){alert('Select a point inside the red campus boundary.');marker.setLatLng([+lat.value,+lng.value]);return;}marker.setLatLng(p);lat.value=p.lat;lng.value=p.lng;q('.coordinates-readout').textContent=`Latitude ${p.lat.toFixed(5)}, longitude ${p.lng.toFixed(5)}`;}
  map.on('click',e=>setPoint(e.latlng));marker.on('dragend',()=>setPoint(marker.getLatLng()));
 }else{
  const colors={critical:'#dc3545',high:'#fd7e14',medium:'#ffc107',low:'#28a745'};
  for(const r of app.reports){const marker=L.marker([r.location.latitude,r.location.longitude],{icon:L.divIcon({className:'concern-map-pin-icon',html:`<span class="concern-map-pin-dot" style="background:${colors[r.severity]||'#6c757d'}"></span>`,iconSize:[18,18],iconAnchor:[9,9]})}).addTo(map);const label=document.createElement('span');label.textContent=r.title;marker.bindTooltip(label);marker.on('click',()=>navigate('map',r.id));if(app.selected===r.id){map.setView(marker.getLatLng(),18);marker.openTooltip();}}
 }
 const resize=new ResizeObserver(()=>map.invalidateSize());resize.observe(mapElement);setTimeout(()=>map.invalidateSize(),200);
 }
}
