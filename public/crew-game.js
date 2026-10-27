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
 let quality='high';
 $('crew-quality').addEventListener('change',e=>{quality=e.target.value;});
 const reduced=matchMedia('(prefers-reduced-motion: reduce)').matches;
 const clearKeys=()=>{keys.clear();drag=null;};
 function reset(){Object.assign(camera,{x:0,y:1.65,z:8,yaw:0,pitch:0});clearKeys();nearest=-1;$('crew-info').hidden=true;}
 function compile(type,source){const shader=gl.createShader(type);gl.shaderSource(shader,source);gl.compileShader(shader);if(!gl.getShaderParameter(shader,gl.COMPILE_STATUS))throw Error('Shader unavailable');return shader;}
 let loc,meshes={},activeMesh='';
 function init(){
  gl=canvas.getContext('webgl',{alpha:false,antialias:true});if(!gl)throw Error('WebGL unavailable');
  program=gl.createProgram();
  gl.attachShader(program,compile(gl.VERTEX_SHADER,`
   attribute vec3 position;attribute vec3 normal;
   uniform vec3 offset;uniform vec3 size;uniform float angle;
   uniform mediump vec3 eye;uniform vec2 look;uniform float aspect;
   varying mediump vec3 world;varying mediump vec3 surfaceNormal;
   void main(){
    vec3 p=position*size;float c=cos(angle),s=sin(angle);
    world=vec3(c*p.x+s*p.z,p.y,-s*p.x+c*p.z)+offset;
    vec3 n=normal/size;surfaceNormal=normalize(vec3(c*n.x+s*n.z,n.y,-s*n.x+c*n.z));
    p=world-eye;
    float cy=cos(look.x),sy=sin(look.x),cp=cos(look.y),sp=sin(look.y);
    p=vec3(cy*p.x+sy*p.z,p.y,-sy*p.x+cy*p.z);
    p=vec3(p.x,cp*p.y+sp*p.z,-sp*p.y+cp*p.z);
    gl_Position=vec4(p.x*1.55/aspect,p.y*1.55,-1.002*p.z-.2002,-p.z);
   }`));
  gl.attachShader(program,compile(gl.FRAGMENT_SHADER,`
   precision mediump float;
   uniform vec3 color;uniform mediump vec3 eye;uniform float material;
   varying mediump vec3 world;varying mediump vec3 surfaceNormal;
   float shadow(vec2 center){return .28*exp(-dot(world.xz-center,world.xz-center)*2.3);}
   void main(){
    vec3 n=normalize(surfaceNormal),base=color;
    float shade=1.0;
    if(material>0.5&&material<1.5){
     float grain=sin(world.z*83.0+sin(world.x*7.0)*2.0)*.018+sin(world.z*217.0)*.008;
     vec2 tile=fract(vec2(world.x*.55,world.z*.16+floor(world.x*.55)*.5));
     float seam=step(.025,tile.x)*step(.012,tile.y);
     base*=.84+grain+.16*seam;
     shade-=shadow(vec2(-3.,-3.))+shadow(vec2(3.,-3.))+shadow(vec2(-5.,1.))+shadow(vec2(5.,1.))+shadow(vec2(-3.,5.))+shadow(vec2(3.,5.));
    }
    vec3 light=normalize(vec3(-.6,1.,.45));
    float diffuse=max(dot(n,light),0.0);
    vec3 view=normalize(eye-world),halfway=normalize(light+view);
    float spec=pow(max(dot(n,halfway),0.0),32.0)*.16;
    vec3 lit=(base*(.48+.46*diffuse)+vec3(1.,.88,.7)*spec)*shade;
    if(material>1.5)lit=base;
    float fog=1.0-exp(-length(eye-world)*.014);
    gl_FragColor=vec4(mix(lit,vec3(.52,.55,.59),fog),1.);
   }`));
  gl.linkProgram(program);if(!gl.getProgramParameter(program,gl.LINK_STATUS))throw Error('Renderer unavailable');gl.useProgram(program);
  const faces=[
   [[[-.5,-.5,.5],[.5,-.5,.5],[.5,.5,.5],[-.5,.5,.5]],[0,0,1]],
   [[[.5,-.5,-.5],[-.5,-.5,-.5],[-.5,.5,-.5],[.5,.5,-.5]],[0,0,-1]],
   [[[-.5,-.5,-.5],[-.5,-.5,.5],[-.5,.5,.5],[-.5,.5,-.5]],[-1,0,0]],
   [[[.5,-.5,.5],[.5,-.5,-.5],[.5,.5,-.5],[.5,.5,.5]],[1,0,0]],
   [[[-.5,.5,.5],[.5,.5,.5],[.5,.5,-.5],[-.5,.5,-.5]],[0,1,0]],
   [[[-.5,-.5,-.5],[.5,-.5,-.5],[.5,-.5,.5],[-.5,-.5,.5]],[0,-1,0]]
  ],vertices=[];for(const [points,normal]of faces)for(const n of [0,1,2,0,2,3])vertices.push(...points[n],...normal);
  function mesh(name,data){const buffer=gl.createBuffer();gl.bindBuffer(gl.ARRAY_BUFFER,buffer);gl.bufferData(gl.ARRAY_BUFFER,new Float32Array(data),gl.STATIC_DRAW);meshes[name]={buffer,count:data.length/6};}
  mesh('box',vertices);
  const curved=[],lat=16,lon=24;
  const point=(i,j)=>{const a=i/lat*Math.PI,b=j/lon*Math.PI*2;const n=[Math.sin(a)*Math.cos(b),Math.cos(a),Math.sin(a)*Math.sin(b)];return [...n.map(v=>v*.5),...n];};
  for(let i=0;i<lat;i++)for(let j=0;j<lon;j++)for(const [a,b]of [[i,j],[i+1,j],[i+1,j+1],[i,j],[i+1,j+1],[i,j+1]])curved.push(...point(a,b));
  mesh('sphere',curved);
  loc={};for(const n of ['offset','size','angle','eye','look','aspect','color','material'])loc[n]=gl.getUniformLocation(program,n);
  loc.position=gl.getAttribLocation(program,'position');loc.normal=gl.getAttribLocation(program,'normal');
  gl.enableVertexAttribArray(loc.position);gl.enableVertexAttribArray(loc.normal);
  gl.enable(gl.DEPTH_TEST);gl.clearColor(.52,.55,.59,1);
  for(const m of members){const label=document.createElement('span');label.className='crew-name';label.textContent=m.name;$('crew-labels').append(label);labels.push(label);}
  initialized=true;
 }
 function shape(kind,x,y,z,w,h,d,color,angle=0,material=0){
  const mesh=meshes[kind];if(activeMesh!==kind){gl.bindBuffer(gl.ARRAY_BUFFER,mesh.buffer);gl.vertexAttribPointer(loc.position,3,gl.FLOAT,false,24,0);gl.vertexAttribPointer(loc.normal,3,gl.FLOAT,false,24,12);activeMesh=kind;}
  gl.uniform3f(loc.offset,x,y,z);gl.uniform3f(loc.size,w,h,d);gl.uniform1f(loc.angle,angle);gl.uniform1f(loc.material,material);gl.uniform3fv(loc.color,color);gl.drawArrays(gl.TRIANGLES,0,mesh.count);
 }
 const box=(...args)=>shape('box',...args),sphere=(...args)=>shape('sphere',...args);
 function drawRoom(){
  const timber=[.65,.49,.34],wall=[.78,.76,.72],metal=[.18,.2,.23];
  box(0,-.12,0,18,.24,20,timber,0,1);
  box(0,2.3,-10,18,4.6,.25,wall);box(-9,2.3,0,.25,4.6,20,wall);box(9,2.3,0,.25,4.6,20,wall);box(0,2.3,10,18,4.6,.25,wall);
  box(0,4.65,0,18,.15,20,[.89,.87,.82]);
  // Ceiling beams and warm linear luminaires.
  for(let z=-8;z<=8;z+=4){box(0,4.45,z,18,.18,.12,[.32,.25,.21]);box(0,4.34,z,5.5,.04,.10,[1.,.9,.7],0,2);}
  for(const x of [-8.82,8.82]){
   box(x,.17,0,.12,.3,20,[.26,.22,.22]);
   for(const z of [-6,0,6]){
    box(x,2.55,z,.08,2.6,3.7,[.6,.77,.87],0,2);
    box(x*.995,1.88,z,.12,1.2,3.65,[.48,.61,.62],0,2);
    for(const dz of [-1.9,0,1.9])box(x*.99,2.55,z+dz,.16,2.8,.07,metal);
    for(const y of [1.2,3.9])box(x*.99,y,z,.16,.07,3.9,metal);
   }
  }
  // A timber feature wall and inset display at the rear of the room.
  for(let x=-3.8;x<=3.8;x+=.3)box(x,2.3,-9.75,.12,4.3,.17,[.4,.28,.21]);
  box(0,2.7,-9.55,5,1.6,.13,[.10,.13,.19]);
  for(let i=0;i<5;i++)box(-1.5+i*.75,2.7,-9.46,.28,.35+i*.12,.02,[.83,.58,.28],0,2);
  // Two workstation desks, screens, keyboards, and chairs.
  for(const x of [-4,4]){
   box(x,.84,-7,2.4,.12,1.1,[.47,.34,.24]);
   for(const dx of [-1,1])box(x+dx,.4,-7,.09,.8,.8,metal);
   box(x,1.3,-7.22,1.1,.65,.06,metal);box(x,1.3,-7.18,1,.55,.015,[.17,.36,.45],0,2);
   box(x,1.,-7.22,.07,.3,.07,metal);box(x,.92,-6.78,.7,.035,.25,[.16,.17,.19]);
   box(x,.52,-5.9,.7,.14,.65,[.28,.22,.28]);box(x,.92,-5.65,.7,.85,.12,[.28,.22,.28]);box(x,.25,-5.9,.12,.5,.12,metal);
  }
  for(const x of [-7.5,7.5])for(const z of [-8,8]){
   sphere(x,.27,z,.75,.54,.75,[.65,.6,.54]);box(x,.8,z,.07,1,.07,[.31,.25,.18]);
   for(let i=0;i<5;i++)sphere(x+Math.cos(i*2)*.24,1.1+i*.15,z+Math.sin(i*2)*.24,.6,.65,.5,[.19+i*.025,.35+i*.02,.23]);
  }
 }
 function avatar(m,index,t){
  const angle=Math.atan2(camera.x-m.x,camera.z-m.z),c=Math.cos(angle),s=Math.sin(angle);
  const bob=reduced?0:Math.sin(t*1.6+index)*.009;
  const part=(x,y,z,w,h,d,color,kind='sphere')=>shape(kind,m.x+c*x+s*z,y+bob,m.z-s*x+c*z,w,h,d,color,angle);
  const skin=[.76,.54,.4],pants=[.15,.18,.23],hair=[.12,.085,.07];
  for(const x of [-.14,.14]){
   part(x,.24,.05,.22,.38,.26,pants);part(x,.58,0,.24,.48,.27,pants);
   part(x,.10,.09,.25,.18,.4,[.16,.14,.14]);part(x,.055,.10,.26,.05,.41,[.72,.7,.66]);
  }
  part(0,1.04,0,.66,.66,.36,m.color);part(0,.77,0,.5,.22,.33,m.color);
  for(const side of [-1,1]){
   part(side*.35,1.14,0,.22,.37,.26,m.color);
   part(side*.4,.9,.015,.17,.32,.19,skin);part(side*.41,.7,.04,.15,.20,.16,skin);
  }
  part(0,1.4,0,.18,.23,.18,skin);
  part(0,1.68,0,.40,.51,.38,skin);part(0,1.88,-.035,.42,.20,.38,hair);
  part(0,1.76,-.145,.4,.29,.12,hair);
  for(const side of [-1,1]){
   part(side*.2,1.67,0,.085,.13,.10,skin);
   part(side*.085,1.71,.169,.085,.045,.028,[.92,.88,.81]);part(side*.085,1.71,.183,.033,.037,.014,[.13,.10,.08]);
   part(side*.085,1.76,.169,.092,.02,.024,hair);
  }
  part(0,1.65,.19,.065,.11,.075,skin);part(0,1.57,.175,.11,.023,.02,[.43,.25,.22]);
  part(0,1.12,.18,.10,.15,.018,[.88,.75,.5],'box');
  // Individual accessories make the six avatars easier to distinguish.
  if(index===0||index===4){for(const x of [-.085,.085])part(x,1.71,.19,.115,.085,.018,[.16,.17,.19],'box');part(0,1.71,.20,.055,.015,.02,[.16,.17,.19],'box');}
  if(index===1)part(0,1.92,.015,.45,.1,.46,[.23,.26,.30]);
  if(index===2)part(-.42,.82,.06,.18,.07,.21,[.12,.15,.18]);
 }
 function project(x,y,z){
  const dx=x-camera.x,dy=y-camera.y,dz=z-camera.z,c=Math.cos(camera.yaw),s=Math.sin(camera.yaw),cp=Math.cos(camera.pitch),sp=Math.sin(camera.pitch);
  const vx=c*dx+s*dz,vz=-s*dx+c*dz,vy=cp*dy+sp*vz,depth=-(-sp*dy+cp*vz);
  return {x:canvas.clientWidth/2+vx*1.55/depth*canvas.clientHeight/2,y:canvas.clientHeight/2-vy*1.55/depth*canvas.clientHeight/2,depth};
 }
 function canMove(x,z){return ![-4,4].some(dx=>Math.abs(x-dx)<1.55&&z>-7.9&&z<-5.1)&&Math.abs(x)<8.45&&Math.abs(z)<9.4&&!members.some(m=>Math.hypot(x-m.x,z-m.z)<.95)&&![-7.5,7.5].some(px=>[-8,8].some(pz=>Math.hypot(x-px,z-pz)<.9));}
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
  const ratio=Math.min(devicePixelRatio||1,quality==='high'?2:1),w=Math.max(1,Math.round(canvas.clientWidth*ratio)),h=Math.max(1,Math.round(canvas.clientHeight*ratio));
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
