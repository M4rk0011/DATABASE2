"""HTTP regression tests. Run: python tests/integration.py (PHP 8.1+ on PATH)."""
from pathlib import Path
import base64, html, http.cookiejar, os, re, socket, subprocess, tempfile, time, urllib.error, urllib.parse, urllib.request
ROOT=Path(__file__).resolve().parents[1]
class Client:
 def __init__(self):
  self.op=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
  self.token=''
 def request(self,page,data=None,files=None):
  headers={}
  if files is not None:
   boundary='----ConcernRegressionBoundary'
   chunks=[]
   for key,value in {'csrf':self.token,**(data or {})}.items():
    for item in value if isinstance(value,list) else [value]:
     chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="{key}"\r\n\r\n{item}\r\n'.encode())
   for name,content,mime in files:
    chunks.append(f'--{boundary}\r\nContent-Disposition: form-data; name="photos[]"; filename="{name}"\r\nContent-Type: {mime}\r\n\r\n'.encode()+content+b'\r\n')
   chunks.append(f'--{boundary}--\r\n'.encode());data=b''.join(chunks)
   headers={'Content-Type':f'multipart/form-data; boundary={boundary}'}
  elif data is not None:data=urllib.parse.urlencode({'csrf':self.token,**data},doseq=True).encode()
  try:r=self.op.open(urllib.request.Request(base+page,data,headers))
  except urllib.error.HTTPError as e:r=e
  text=r.read().decode();m=re.search(r'name="csrf" value="([^"]+)"',text)
  if m:self.token=m[1]
  assert not re.search(r'(Fatal error|Warning(?:</b>)?:|Parse error)',text),text
  return r.status,text,r.url
 def login(self,name,password):
  self.request('login');status,text,_=self.request('login',{'action':'login','username':name,'password':password});assert 'Welcome,' in text

def check(label,condition):
 assert condition,label
 print('PASS',label)
with tempfile.TemporaryDirectory() as temp:
 env=os.environ.copy();env['APP_SQLITE_PATH']=str(Path(temp)/'test.sqlite');env['APP_UPLOAD_PATH']=str(Path(temp)/'uploads');php=env.get('PHP_BIN','php')
 subprocess.run([php,'bin/setup.php','--demo'],cwd=ROOT,env=env,check=True,stdout=subprocess.DEVNULL)
 with socket.socket() as s:s.bind(('127.0.0.1',0));port=s.getsockname()[1]
 base=f'http://127.0.0.1:{port}/index.php?page='
 log=open(Path(temp)/'server.log','w')
 server=subprocess.Popen([php,'-S',f'127.0.0.1:{port}','-t','public','public/router.php'],cwd=ROOT,env=env,stdout=log,stderr=log)
 try:
  time.sleep(.5)
  guest=Client();check('Authentication gate','page=login' in guest.request('dashboard')[2])
  _,failed,_=guest.request('login',{'action':'login','username':'student','password':'wrong-secret'})
  check('Login error shown once inside form',failed.count('Invalid username or password.')==1 and failed.index('class="login-form"')<failed.index('id="login-error"')<failed.index('</form>'))
  check('Failed login keeps username, clears password','value="student"' in failed and 'wrong-secret' not in failed and 'app-message app-error' not in failed)
  check('Team credits and playable studio rendered','Mark Lloyd Yadao' in failed and 'Bryan Dave Balboa' in failed and 'id="crew-start"' in failed and 'id="crew-canvas"' in failed and 'team-card' not in failed)
  guest.request('register');d={'action':'register','firstName':'Test','lastName':'Student','username':'test-user','email':'test@example.com','password':'test1234','confirmPassword':'test1234'}
  check('Registration','Account created' in guest.request('register',d)[1]);check('Duplicate account rejected','already exists' in guest.request('register',d)[1])
  guest.login('test-user','test1234');check('New account persists',True)
  student=Client();student.login('student','student123');admin=Client();admin.login('admin','admin123');security=Client();security.login('security','security123')
  for p in ['dashboard','concerns','details&id=1','map','map&id=1','home','analytics','sdg-impact','dashboard&editUser=3']:
   check('Render '+p,admin.request(p)[0]==200)
  check('Studio excluded from dashboard and registration',all('crew-canvas' not in admin.request(page)[1] and 'crew-game.js' not in admin.request(page)[1] for page in ['dashboard','register']))
  report={'action':'save-report','title':'Integration <script>alert(1)</script>','description':'Description','category':'facility','severity':'high','address':'Main Library','latitude':'16.41555','longitude':'120.5975','contactName':'Maria','contactEmail':'student@ub.edu.ph','witnesses[]':['Witness A','Witness B'],'additionalNotes':'Notes'}
  status,text,url=student.request('report',report);rid=urllib.parse.parse_qs(urllib.parse.urlparse(url).query)['id'][0]
  check('Create report and escape HTML','&lt;script&gt;' in text and 'Witness B' in text)
  png=base64.b64decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+jXioAAAAASUVORK5CYII=')
  _,text,_=student.request('report&id='+rid,{**report,'id':rid},[('photo.png',png,'image/png')])
  photo=html.unescape(re.search(r'src="(photo.php[^" ]+)"',text)[1])
  photo_name=urllib.parse.parse_qs(urllib.parse.urlparse(photo).query)['name'][0]
  photo_url=base.split('index.php')[0]+photo
  response=student.op.open(photo_url)
  check('Photo uploaded and served',response.read()==png and response.headers['Content-Type']=='image/png')
  try:urllib.request.build_opener(urllib.request.ProxyHandler({})).open(photo_url);raise AssertionError('Public photo leaked')
  except urllib.error.HTTPError as ex:check('Photo requires login',ex.code==401)
  check('Fake image rejected','Only valid' in student.request('report&id='+rid,{**report,'id':rid},[('fake.png',b'<?php echo 1; ?>','image/png')])[1])
  check('Oversized image rejected','2 MB' in student.request('report&id='+rid,{**report,'id':rid},[('big.png',png+b'0'*(2*1024*1024),'image/png')])[1])
  check('Photo count enforced','Maximum 5 photos' in student.request('report&id='+rid,{**report,'id':rid},[('x.png',png,'image/png')]*5)[1])
  check('Non-owner cannot attach','permission' in guest.request('details&id='+rid,{**report,'id':rid},[('x.png',png,'image/png')])[1])
  check('Failed uploads leave no files',len(list(Path(env['APP_UPLOAD_PATH']).iterdir()))==1)
  student.request('report&id='+rid,{**report,'id':rid,'removePhotos[]':[photo_name]})
  check('Photo removal deletes file',not list(Path(env['APP_UPLOAD_PATH']).iterdir()))
  student.request('report&id='+rid,{**report,'id':rid},[('new.png',png,'image/png')])
  check('Search filter','No concerns found' in student.request('concerns&search=zznonexistent')[1])
  check('Status filter', 'Integration' not in student.request('concerns&statusFilter=closed')[1])
  check('Campus boundary validation','campus boundary' in student.request('report',{**report,'latitude':'0'})[1])
  check('Non-owner cannot edit',guest.request('report&id='+rid)[0]==403)
  check('Non-owner POST cannot edit','permission' in guest.request('details&id='+rid,{**report,'id':rid})[1])
  check('Student cannot change status','permission' in student.request('details&id='+rid,{'action':'status','id':rid,'value':'closed'})[1])
  check('CSRF required',student.request('details&id='+rid,{'action':'delete-report','id':rid,'csrf':'bad'})[0]==403)
  check('Edit report','Updated title' in student.request('report&id='+rid,{**report,'id':rid,'title':'Updated title'})[1])
  check('Staff status update','RESOLVED' in security.request('details&id='+rid,{'action':'status','id':rid,'value':'resolved'})[1])
  check('Staff assignment','facilities' in security.request('map&id='+rid,{'action':'assign','id':rid,'value':'facilities'})[1])
  check('Staff cannot delete','permission' in security.request('details&id='+rid,{'action':'delete-report','id':rid})[1])
  check('Admin edit user','Updated Student' in admin.request('dashboard',{'action':'save-user','id':'3','username':'student','email':'student@ub.edu.ph','role':'user','firstName':'Updated','lastName':'Student','password':''})[1])
  check('Admin creates security account','staff-new' in admin.request('dashboard',{'action':'create-user','username':'staff-new','email':'staff-new@example.com','password':'test1234','firstName':'New','lastName':'Staff','role':'security'})[1])
  check('Admin account protected','permission' in admin.request('dashboard',{'action':'delete-user','id':'1'})[1])
  admin.request('details&id='+rid,{'action':'delete-report','id':rid});check('Report deleted',admin.request('details&id='+rid)[0]==404);check('Deleting report removes photos',not list(Path(env['APP_UPLOAD_PATH']).iterdir()))
  check('Anonymous report','Anonymous' in student.request('report',{**report,'isAnonymous':'on','contactName':'','contactEmail':''})[1])
  admin.request('dashboard',{'action':'delete-user','id':'5'});check('User deleted','student2@ub.edu.ph' not in admin.request('dashboard')[1])
  check('Logout','page=login' in student.request('dashboard',{'action':'logout'})[2]);check('Logged-out session rejected','page=login' in student.request('dashboard')[2])
 finally:server.terminate();server.wait();log.close()
print('All integration checks passed.')
