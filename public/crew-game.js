/* Local WebGL studio: no CDN, textures, tracking, or external game engine. */
(() => {
 'use strict';
 const root=document.getElementById('crew-game');if(!root)return;
 const $=id=>document.getElementById(id),canvas=$('crew-canvas'),start=$('crew-start');
 const members=[
  {name:'Mark Lloyd Yadao',role:'Frontend & backend developer',x:-3,z:-3,color:[.67,.13,.29]},
  {name:'Bryan Dave Balboa',role:'Website co-developer & design',x:3,z:-3,color:[.19,.43,.57]},
  {name:'Ace Danioan',role:'Page drafts & report assistance',x:-5,z:1,color:[.35,.47,.31]},
  {name:'Dhaff Kindipan',role:'Dashboard & navigation assistance',x:5,z:1,color:[.46,.31,.63]},
  {name:'Derek Vinoya',role:'Map drafts & interface assistance',x:-3,z:5,color:[.71,.43,.19]},
  {name:'Sean Ballug',role:'Initial planning & suggestions',x:3,z:5,color:[.24,.49,.49]}
 ];
 let gl,program,running=false,initialized=false,frameId=0,last=0,drag=null,nearest=-1,visible=true;
 const keys=new Set(),met=new Set(),camera={x:0,y:1.65,z:8,yaw:0,pitch:0},labels=[];
 const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
 const clearKeys=()=>{keys.clear();drag=null;};
 function reset(){Object.assign(camera,{x:0,y:1.65,z:8,yaw:0,pitch:0});clearKeys();nearest=-1;$('crew-info').hidden=true;}
 function compile(type,source){const shader=gl.createShader(type);gl.shaderSource(shader,source);gl.compileShader(shader);if(!gl.getShaderParameter(shader,gl.COMPILE_STATUS))throw Error('Shader unavailable');return shader;}
 let loc;
 function init(){
  gl=canvas.getContext('webgl',{alpha:false,antialias:true});if(!gl)throw Error('WebGL unavailable');
  program=gl.createProgram();
  gl.attachShader(program,compile(gl.VERTEX_SHADER,`
   attribute vec3 position;attribute float shade;
   uniform vec3 offset;uniform vec3 size;uniform float angle;
   uniform vec3 eye;uniform vec2 look;uniform float aspect;
   varying float light;
   void main(){
    vec3 p=position*size;float c=cos(angle),s=sin(angle);
    p=vec3(c*p.x+s*p.z,p.y,-s*p.x+c*p.z)+offset-eye;
    float cy=cos(look.x),sy=sin(look.x),cp=cos(look.y),sp=sin(look.y);
    p=vec3(cy*p.x+sy*p.z,p.y,-sy*p.x+cy*p.z);
    p=vec3(p.x,cp*p.y+sp*p.z,-sp*p.y+cp*p.z);
    gl_Position=vec4(p.x*1.55/aspect,p.y*1.55,-1.002*p.z-.2002,-p.z);light=shade;
   }`));
  gl.attachShader(program,compile(gl.FRAGMENT_SHADER,`precision mediump float;uniform vec3 color;varying float light;void main(){gl_FragColor=vec4(color*light,1.0);}`));
  gl.linkProgram(program);if(!gl.getProgramParameter(program,gl.LINK_STATUS))throw Error('Renderer unavailable');gl.useProgram(program);
  const faces=[
   [[[-.5,-.5,.5],[.5,-.5,.5],[.5,.5,.5],[-.5,.5,.5]],.92],
   [[[.5,-.5,-.5],[-.5,-.5,-.5],[-.5,.5,-.5],[.5,.5,-.5]],.65],
   [[[-.5,-.5,-.5],[-.5,-.5,.5],[-.5,.5,.5],[-.5,.5,-.5]],.72],
   [[[.5,-.5,.5],[.5,-.5,-.5],[.5,.5,-.5],[.5,.5,.5]],.82],
   [[[-.5,.5,.5],[.5,.5,.5],[.5,.5,-.5],[-.5,.5,-.5]],1],
   [[[-.5,-.5,-.5],[.5,-.5,-.5],[.5,-.5,.5],[-.5,-.5,.5]],.55]
  ],vertices=[];for(const [points,shade]of faces)for(const n of [0,1,2,0,2,3])vertices.push(...points[n],shade);
  gl.bindBuffer(gl.ARRAY_BUFFER,gl.createBuffer());gl.bufferData(gl.ARRAY_BUFFER,new Float32Array(vertices),gl.STATIC_DRAW);
  for(const [name,count,offset]of [['position',3,0],['shade',1,12]]){const a=gl.getAttribLocation(program,name);gl.enableVertexAttribArray(a);gl.vertexAttribPointer(a,count,gl.FLOAT,false,16,offset);}
  loc={};for(const n of ['offset','size','angle','eye','look','aspect','color'])loc[n]=gl.getUniformLocation(program,n);
  gl.enable(gl.DEPTH_TEST);gl.clearColor(.12,.09,.15,1);
  for(const m of members){const label=document.createElement('span');label.className='crew-name';label.textContent=m.name;$('crew-labels').append(label);labels.push(label);}
  initialized=true;
 }
 function box(x,y,z,w,h,d,color,angle=0){gl.uniform3f(loc.offset,x,y,z);gl.uniform3f(loc.size,w,h,d);gl.uniform1f(loc.angle,angle);gl.uniform3fv(loc.color,color);gl.drawArrays(gl.TRIANGLES,0,36);}
 function drawRoom(){
  box(0,-.12,0,18,.24,20,[.31,.26,.33]);
  for(let i=-8;i<=8;i+=2){box(i,.006,0,.018,.012,20,[.46,.38,.44]);box(0,.006,i,18,.012,.018,[.46,.38,.44]);}
  box(0,2.3,-10,18,4.6,.25,[.34,.15,.27]);box(-9,2.3,0,.25,4.6,20,[.22,.19,.29]);box(9,2.3,0,.25,4.6,20,[.22,.19,.29]);box(0,2.3,10,18,4.6,.25,[.28,.18,.26]);
  box(0,2.5,-9.8,6,1.5,.12,[.11,.10,.17]);box(0,3.35,-9.7,6.3,.065,.1,[.95,.72,.39]);box(0,1.65,-9.7,6.3,.065,.1,[.95,.72,.39]);
  // Gold geometric crest, plus light panels along the side walls.
  box(0,2.5,-9.6,.65,.65,.13,[.95,.72,.39],Math.PI/4);
  for(const x of [-8.82,8.82])for(const z of [-6,0,6]){box(x,2.5,z,.06,1.5,2.8,[.68,.76,.8]);box(x,2.5,z,.1,.04,2.8,[.17,.19,.26]);}
  box(0,.028,1,1.8,.04,15,[.47,.17,.31]);
  for(const x of [-7.5,7.5])for(const z of [-8,8]){box(x,.25,z,.65,.5,.65,[.53,.39,.32]);box(x,.9,z,.8,.85,.8,[.22,.42,.29]);box(x,1.4,z,.6,.5,.6,[.32,.52,.35]);}
 }
 function avatar(m,index,t){
  const angle=Math.atan2(camera.x-m.x,camera.z-m.z),c=Math.cos(angle),s=Math.sin(angle);
  const bob=reduced?0:Math.sin(t*1.6+index)*.025;
  const part=(x,y,z,w,h,d,color)=>box(m.x+c*x+s*z,y+bob,m.z-s*x+c*z,w,h,d,color,angle);
  box(m.x,.06,m.z,1.3,.12,1.3,[.48,.33,.40]);
  const skin=[.76,.52,.36],pants=[.13,.16,.23],hair=[.13,.10,.12];
  part(-.17,.47,0,.23,.7,.27,pants);part(.17,.47,0,.23,.7,.27,pants);
  part(-.17,.16,.08,.27,.14,.43,[.1,.09,.13]);part(.17,.16,.08,.27,.14,.43,[.1,.09,.13]);
  part(0,1.05,0,.7,.65,.38,m.color);
  part(-.46,1.0,0,.19,.66,.25,m.color);part(.46,1.03,0,.19,.66,.25,m.color);
  part(-.46,.65,0,.18,.16,.22,skin);part(.46,.68,0,.18,.16,.22,skin);
  part(0,1.64,0,.5,.53,.47,skin);part(0,1.92,-.02,.53,.13,.5,hair);part(0,1.72,-.23,.52,.34,.06,hair);
  for(const x of [-.13,.13])part(x,1.68,.242,.065,.07,.02,[.08,.07,.1]);
  part(0,1.51,.244,.15,.025,.02,[.32,.14,.15]);
  part(0,1.12,.20,.14,.13,.02,[.96,.77,.46]);
 }
 function project(x,y,z){
  const dx=x-camera.x,dy=y-camera.y,dz=z-camera.z,c=Math.cos(camera.yaw),s=Math.sin(camera.yaw),cp=Math.cos(camera.pitch),sp=Math.sin(camera.pitch);
  const vx=c*dx+s*dz,vz=-s*dx+c*dz,vy=cp*dy+sp*vz,depth=-(-sp*dy+cp*vz);
  return {x:canvas.clientWidth/2+vx*1.55/depth*canvas.clientHeight/2,y:canvas.clientHeight/2-vy*1.55/depth*canvas.clientHeight/2,depth};
 }
 function canMove(x,z){return Math.abs(x)<8.45&&Math.abs(z)<9.4&&!members.some(m=>Math.hypot(x-m.x,z-m.z)<.95)&&![-7.5,7.5].some(px=>[-8,8].some(pz=>Math.hypot(x-px,z-pz)<.9));}
 function update(dt){
  if(keys.has('arrowleft'))camera.yaw-=dt*1.6;if(keys.has('arrowright'))camera.yaw+=dt*1.6;
  let f=Number(keys.has('w')||keys.has('arrowup'))-Number(keys.has('s')||keys.has('arrowdown')),r=Number(keys.has('d'))-Number(keys.has('a'));
  const length=Math.hypot(f,r)||1;f/=length;r/=length;
  const dx=(Math.sin(camera.yaw)*f+Math.cos(camera.yaw)*r)*dt*3,dz=(-Math.cos(camera.yaw)*f+Math.sin(camera.yaw)*r)*dt*3;
  if(canMove(camera.x+dx,camera.z))camera.x+=dx;if(canMove(camera.x,camera.z+dz))camera.z+=dz;
  let close=-1,distance=2.65;members.forEach((m,i)=>{const d=Math.hypot(camera.x-m.x,camera.z-m.z);if(d<distance){distance=d;close=i;}});
  if(close!==nearest){nearest=close;const info=$('crew-info');info.hidden=close<0;if(close>=0){met.add(close);info.replaceChildren();const n=document.createElement('strong'),r=document.createElement('span');n.textContent=members[close].name;r.textContent=members[close].role;info.append(n,r);$('crew-count').textContent=met.size+' / 6 met';}}
 }
 function draw(time){
  if(!running||!visible)return;
  const dt=Math.min((time-last)/1000,.04);last=time;update(dt);
  const ratio=Math.min(devicePixelRatio||1,1.5),w=Math.max(1,Math.round(canvas.clientWidth*ratio)),h=Math.max(1,Math.round(canvas.clientHeight*ratio));
  if(canvas.width!==w||canvas.height!==h){canvas.width=w;canvas.height=h;}gl.viewport(0,0,w,h);gl.clear(gl.COLOR_BUFFER_BIT|gl.DEPTH_BUFFER_BIT);
  gl.uniform3f(loc.eye,camera.x,camera.y,camera.z);gl.uniform2f(loc.look,camera.yaw,camera.pitch);gl.uniform1f(loc.aspect,w/h);
  drawRoom();members.forEach((m,i)=>{avatar(m,i,time/1000);const p=project(m.x,2.2,m.z),label=labels[i];label.hidden=p.depth<.4||p.x<0||p.x>canvas.clientWidth||p.y<0||p.y>canvas.clientHeight;if(!label.hidden){label.style.left=p.x+'px';label.style.top=p.y+'px';}});
  frameId=requestAnimationFrame(draw);
 }
 function pause(){running=false;clearKeys();cancelAnimationFrame(frameId);$('crew-overlay').hidden=false;for(const id of ['crew-hud','crew-controls','crew-crosshair','crew-info'])$(id).hidden=true;start.textContent='Resume exploring →';}
 start.addEventListener('click',()=>{
  try{if(!initialized)init();}catch(error){start.disabled=true;start.textContent='3D is unavailable on this browser';root.querySelector('.crew-overlay p').textContent='You can still read every member’s contribution in the team credits below.';return;}
  running=true;clearKeys();nearest=-1;$('crew-overlay').hidden=true;for(const id of ['crew-hud','crew-controls','crew-crosshair'])$(id).hidden=false;canvas.focus();last=performance.now();cancelAnimationFrame(frameId);frameId=requestAnimationFrame(draw);
 });
 $('crew-exit').addEventListener('click',()=>{pause();start.focus();});$('crew-reset').addEventListener('click',()=>{reset();canvas.focus();});
 const allowed=new Set(['w','a','s','d','arrowleft','arrowright','arrowup','arrowdown']);
 root.addEventListener('keydown',e=>{if(!running)return;const k=e.key.toLowerCase();if(k==='escape'){e.preventDefault();pause();start.focus();}else if(allowed.has(k)){e.preventDefault();keys.add(k);}});
 window.addEventListener('keyup',e=>keys.delete(e.key.toLowerCase()));window.addEventListener('blur',()=>{if(running)pause();});
 root.addEventListener('focusout',e=>{if(running&&!root.contains(e.relatedTarget))pause();});
 canvas.addEventListener('pointerdown',e=>{if(!running)return;canvas.focus();canvas.setPointerCapture(e.pointerId);drag={id:e.pointerId,x:e.clientX,y:e.clientY};});
 canvas.addEventListener('pointermove',e=>{if(!running||!drag||drag.id!==e.pointerId)return;camera.yaw+=(e.clientX-drag.x)*.005;camera.pitch=Math.max(-.55,Math.min(.55,camera.pitch-(e.clientY-drag.y)*.004));drag.x=e.clientX;drag.y=e.clientY;});
 for(const type of ['pointerup','pointercancel','lostpointercapture'])canvas.addEventListener(type,()=>{drag=null;});
 for(const button of root.querySelectorAll('[data-crew-key]')){
  button.addEventListener('pointerdown',e=>{if(!running)return;e.preventDefault();button.setPointerCapture(e.pointerId);keys.add(button.dataset.crewKey);});
  for(const type of ['pointerup','pointercancel','lostpointercapture'])button.addEventListener(type,()=>keys.delete(button.dataset.crewKey));
  // Enter/Space offers a short step for keyboard users of the touch controls.
  button.addEventListener('click',e=>{if(e.detail===0&&running){keys.add(button.dataset.crewKey);setTimeout(()=>keys.delete(button.dataset.crewKey),180);}});
 }
 document.addEventListener('visibilitychange',()=>{if(document.hidden&&running)pause();});
 if('IntersectionObserver'in window)new IntersectionObserver(entries=>{visible=entries[0].isIntersecting;if(!visible&&running)pause();},{threshold:.05}).observe(root);
 canvas.addEventListener('webglcontextlost',e=>{e.preventDefault();pause();start.disabled=true;start.textContent='3D paused — reload to restart';});
})();
