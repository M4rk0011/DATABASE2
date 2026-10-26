// Network-independent regression tests for map tile lifecycle and recovery.
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../public/app.js'),'utf8').split('// MAP_BACKGROUND_START')[1].split('// MAP_BACKGROUND_END')[0];
function fixture(){
 const layers=[],timers=new Map();let sequence=0;
 const ctx={select:{},retry:{},notice:{},mapElement:{dataset:{}},bounds:{pad:()=>({})},map:{removeLayer:l=>{l.removed=true;}},Grid:function(){this.addTo=()=>this;},setTimeout:fn=>{timers.set(++sequence,fn);return sequence;},clearTimeout:id=>timers.delete(id)};
 ctx.L={tileLayer:(url,options)=>{const handlers={};const layer={url,options,on:(event,fn)=>{handlers[event]=fn;return layer;},addTo:()=>{layers.push(layer);layer.emit('loading');return layer;},emit:event=>handlers[event]?.()};return layer;}};
 vm.runInNewContext('// MAP_BACKGROUND_START'+source,ctx);
 return {ctx,layers,timers,last:()=>layers.at(-1),complete:(good,bad=0)=>{const l=layers.at(-1);for(let i=0;i<good;i++)l.emit('tileload');for(let i=0;i<bad;i++)l.emit('tileerror');l.emit('load');},timeout:()=>{const fn=[...timers.values()].at(-1);assert.ok(fn);fn();}};
}
let count=0;function test(name,fn){fn();console.log('PASS '+name);count++;}
test('Street starts on Esri with native zoom 18',()=>{const f=fixture();assert.match(f.last().url,/World_Street_Map/);assert.equal(f.last().options.maxNativeZoom,18);});
test('Successful complete batch stays on selected provider',()=>{const f=fixture();f.complete(4);assert.equal(f.layers.length,1);assert.equal(f.timers.size,0);assert.match(f.ctx.notice.textContent,/loaded/);});
test('One success does not hide a partial failure',()=>{const f=fixture();f.complete(1,3);assert.match(f.last().url,/openstreetmap/);});
test('One success does not cancel timeout for stalled tiles',()=>{const f=fixture();f.last().emit('tileload');f.timeout();assert.match(f.last().url,/openstreetmap/);});
test('Street failures try all street providers then labeled satellite',()=>{const f=fixture();f.complete(0,4);f.complete(0,4);assert.match(f.last().url,/cartocdn/);f.complete(0,4);assert.match(f.last().url,/World_Imagery/);f.complete(4);assert.equal(f.ctx.select.value,'satellite');assert.match(f.ctx.notice.textContent,/Street providers unavailable/);});
test('All-provider failure shows offline grid without retry loop',()=>{const f=fixture();for(let i=0;i<4;i++)f.complete(0,1);assert.equal(f.layers.length,4);assert.equal(f.ctx.select.value,'offline');assert.equal(f.timers.size,0);});
test('Errors after zoom/pan trigger recovery',()=>{const f=fixture();f.complete(4);f.last().emit('loading');f.complete(2,1);assert.equal(f.layers.length,2);});
test('Stale provider events cannot overwrite user-selected satellite',()=>{const f=fixture(),old=f.last();f.ctx.select.value='satellite';f.ctx.select.onchange();old.emit('tileerror');old.emit('load');assert.match(f.last().url,/World_Imagery/);assert.equal(f.layers.length,2);});
test('Alternate mode falls back to street providers',()=>{const f=fixture();f.ctx.select.value='alternate';f.ctx.select.onchange();assert.match(f.last().url,/cartocdn/);f.complete(0,1);assert.match(f.last().url,/openstreetmap/);});
test('Retry honors the requested mode after automatic fallback',()=>{const f=fixture();for(let i=0;i<4;i++)f.complete(0,1);f.ctx.retry.onclick();assert.match(f.last().url,/World_Street_Map/);});
console.log(count+' map lifecycle checks passed.');
