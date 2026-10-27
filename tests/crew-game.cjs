// Exercise studio interaction without requiring a GPU; shader rendering still needs a browser.
const fs=require('node:fs'),vm=require('node:vm'),assert=require('node:assert/strict');
const source=fs.readFileSync(require('node:path').join(__dirname,'../public/crew-game.js'),'utf8');
function harness(webgl=true){
 const elements=new Map(),frames=new Map(),uniforms={},handlers={};let next=1;
 function element(id){return {id,hidden:false,disabled:false,textContent:'',style:{},dataset:{},children:[],events:{},clientWidth:1000,clientHeight:540,
 addEventListener(t,f){(this.events[t]??=[]).push(f)},emit(t,e={}){for(const f of this.events[t]||[])f({preventDefault(){},...e})},append(...e){this.children.push(...e)},replaceChildren(){this.children=[]},focus(){},setPointerCapture(){},contains(){return true},querySelector(){return element('paragraph')},querySelectorAll(){return []}};}
 const get=id=>{if(!elements.has(id))elements.set(id,element(id));return elements.get(id)};
 const gl=new Proxy({getShaderParameter:()=>true,getProgramParameter:()=>true,getUniformLocation:(_,n)=>n,getAttribLocation:()=>0,uniform3f:(n,...v)=>uniforms[n]=v,uniform2f:(n,...v)=>uniforms[n]=v},{get:(o,k)=>k in o?o[k]:()=>({})});get('crew-canvas').getContext=()=>webgl?gl:null;
 const document={getElementById:get,createElement:()=>element('new'),addEventListener:(t,f)=>handlers[t]=f,hidden:false};
 vm.runInNewContext(source,{document,window:{addEventListener:(t,f)=>handlers[t]=f},matchMedia:()=>({matches:false}),devicePixelRatio:1,performance:{now:()=>0},requestAnimationFrame:f=>{const n=next++;frames.set(n,f);return n},cancelAnimationFrame:n=>frames.delete(n),setTimeout:f=>f(),console});
 let time=0;function step(n=1){for(let i=0;i<n;i++){time+=16;const pending=[...frames.values()];frames.clear();for(const f of pending)f(time)}}
 return {get,uniforms,frames,handlers,document,step};
}
let h=harness();h.get('crew-start').emit('click');h.step();assert.equal(h.get('crew-overlay').hidden,true);assert.equal(h.get('crew-labels').children.length,6);
h.get('crew-game').emit('keydown',{key:'w'});h.step(100);assert(h.uniforms.eye[2]<4,'walking advances camera');
h.get('crew-game').emit('keydown',{key:'d'});h.step(35);h.handlers.keyup({key:'w'});h.step(300);assert(h.uniforms.eye[0]<8.45,'room boundary blocks walking');
h.handlers.keyup({key:'d'});h.get('crew-reset').emit('click');h.step();assert.deepEqual(h.uniforms.eye,[0,1.65,8]);
h.get('crew-game').emit('keydown',{key:'w'});h.step(60);h.handlers.keyup({key:'w'});h.get('crew-game').emit('keydown',{key:'d'});h.step(60);h.handlers.keyup({key:'d'});assert.notEqual(h.get('crew-count').textContent,'','approach reveals a contribution');assert.equal(h.get('crew-info').hidden,false);
h.get('crew-game').emit('keydown',{key:'Escape'});assert.equal(h.frames.size,0);assert.equal(h.get('crew-overlay').hidden,false);
h.get('crew-start').emit('click');h.step();h.handlers.blur();assert.equal(h.frames.size,0,'tab blur stops animation');
h=harness(false);h.get('crew-start').emit('click');assert.equal(h.get('crew-start').disabled,true);assert.match(h.get('crew-start').textContent,/unavailable/);assert.equal(h.frames.size,0);
console.log('PASS studio entry, six avatars, movement, collision, reset, proximity credits, pause, focus loss, and WebGL fallback');
