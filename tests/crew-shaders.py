"""Optional GPU shader check: run from repository root on Linux with Mesa EGL installed."""
import ctypes as C,re,os
from pathlib import Path
os.environ['EGL_PLATFORM']='surfaceless'
e=C.CDLL('libEGL.so.1');ptr=C.c_void_p;integer=C.c_int
def fn(name,result,args):
 f=getattr(e,name);f.restype=result;f.argtypes=args;return f
d=fn('eglGetDisplay',ptr,[ptr])(None)
assert fn('eglInitialize',C.c_uint,[ptr,ptr,ptr])(d,None,None),'EGL unavailable'
fn('eglBindAPI',C.c_uint,[C.c_uint])(0x30A0)
a=(integer*13)(0x3033,1,0x3040,4,0x3024,8,0x3023,8,0x3022,8,0x3025,24,0x3038);cfg=ptr();n=integer()
assert fn('eglChooseConfig',C.c_uint,[ptr,ptr,ptr,integer,ptr])(d,a,C.byref(cfg),1,C.byref(n)) and n.value
context=fn('eglCreateContext',ptr,[ptr,ptr,ptr,ptr])(d,cfg,None,(integer*3)(0x3098,2,0x3038))
surf=fn('eglCreatePbufferSurface',ptr,[ptr,ptr,ptr])(d,cfg,(integer*5)(0x3057,1000,0x3056,620,0x3038))
assert fn('eglMakeCurrent',C.c_uint,[ptr,ptr,ptr,ptr])(d,surf,surf,context)
proc=fn('eglGetProcAddress',ptr,[C.c_char_p])
def gl(name,result,*args):return C.CFUNCTYPE(result,*args)(proc(name.encode()))
create=gl('glCreateShader',C.c_uint,C.c_uint);source=gl('glShaderSource',None,C.c_uint,integer,ptr,ptr);compile=gl('glCompileShader',None,C.c_uint);status=gl('glGetShaderiv',None,C.c_uint,C.c_uint,ptr);log=gl('glGetShaderInfoLog',None,C.c_uint,integer,ptr,ptr)
s=Path('public/crew-game.js').read_text();shaders=[]
for kind,code in re.findall(r'compile\(gl\.(VERTEX_SHADER|FRAGMENT_SHADER),`(.*?)`\)',s,re.S):
 sh=create(0x8B31 if kind=='VERTEX_SHADER' else 0x8B30);raw=C.c_char_p(code.encode());source(sh,1,C.byref(raw),None);compile(sh);ok=integer();status(sh,0x8B81,C.byref(ok));buf=C.create_string_buffer(4096);log(sh,4096,None,buf);assert ok.value,buf.value.decode();shaders.append(sh);print('PASS',kind,'compiled on Mesa EGL')
program=gl('glCreateProgram',C.c_uint)()
for sh in shaders:gl('glAttachShader',None,C.c_uint,C.c_uint)(program,sh)
gl('glLinkProgram',None,C.c_uint)(program);ok=integer();gl('glGetProgramiv',None,C.c_uint,C.c_uint,ptr)(program,0x8B82,C.byref(ok));buf=C.create_string_buffer(4096);gl('glGetProgramInfoLog',None,C.c_uint,integer,ptr,ptr)(program,4096,None,buf);assert ok.value,buf.value.decode();print('PASS lighting program linked')
