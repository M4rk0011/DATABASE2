"""HTTP regression tests. Run: python tests/integration.py (PHP 8.1+ on PATH)."""
from pathlib import Path
import http.cookiejar, os, re, socket, subprocess, tempfile, time, urllib.error, urllib.parse, urllib.request
ROOT=Path(__file__).resolve().parents[1]
class Client:
 def __init__(self):
  self.op=urllib.request.build_opener(urllib.request.ProxyHandler({}),urllib.request.HTTPCookieProcessor(http.cookiejar.CookieJar()))
  self.token=''
 def request(self,page,data=None):
  if data is not None:data=urllib.parse.urlencode({'csrf':self.token,**data},doseq=True).encode()
  try:r=self.op.open(base+page,data)
  except urllib.error.HTTPError as e:r=e
  text=r.read().decode();m=re.search(r'name="csrf" value="([^"]+)"',text)
  if m:self.token=m[1]
  assert not re.search(r'(Fatal error|Warning:|Parse error)',text),text
  return r.status,text,r.url
 def login(self,name,password):
  self.request('login');status,text,_=self.request('login',{'action':'login','username':name,'password':password});assert 'Welcome,' in text

def check(label,condition):
 assert condition,label
 print('PASS',label)
with tempfile.TemporaryDirectory() as temp:
 env=os.environ.copy();env['APP_SQLITE_PATH']=str(Path(temp)/'test.sqlite');php=env.get('PHP_BIN','php')
 subprocess.run([php,'bin/setup.php','--demo'],cwd=ROOT,env=env,check=True,stdout=subprocess.DEVNULL)
 with socket.socket() as s:s.bind(('127.0.0.1',0));port=s.getsockname()[1]
 base=f'http://127.0.0.1:{port}/index.php?page='
 log=open(Path(temp)/'server.log','w')
 server=subprocess.Popen([php,'-S',f'127.0.0.1:{port}','-t','public','public/router.php'],cwd=ROOT,env=env,stdout=log,stderr=log)
 try:
  time.sleep(.5)
  guest=Client();check('Authentication gate','page=login' in guest.request('dashboard')[2])
  guest.request('register');d={'action':'register','firstName':'Test','lastName':'Student','username':'test-user','email':'test@example.com','password':'test1234','confirmPassword':'test1234'}
  check('Registration','Account created' in guest.request('register',d)[1]);check('Duplicate account rejected','already exists' in guest.request('register',d)[1])
  guest.login('test-user','test1234');check('New account persists',True)
  student=Client();student.login('student','student123');admin=Client();admin.login('admin','admin123');security=Client();security.login('security','security123')
  for p in ['dashboard','concerns','details&id=1','map','map&id=1','home','analytics','sdg-impact','dashboard&editUser=3']:
   check('Render '+p,admin.request(p)[0]==200)
  report={'action':'save-report','title':'Integration <script>alert(1)</script>','description':'Description','category':'facility','severity':'high','address':'Main Library','latitude':'16.41555','longitude':'120.5975','contactName':'Maria','contactEmail':'student@ub.edu.ph','witnesses[]':['Witness A','Witness B'],'additionalNotes':'Notes'}
  status,text,url=student.request('report',report);rid=urllib.parse.parse_qs(urllib.parse.urlparse(url).query)['id'][0]
  check('Create report and escape HTML','&lt;script&gt;' in text and 'Witness B' in text)
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
  admin.request('details&id='+rid,{'action':'delete-report','id':rid});check('Report deleted',admin.request('details&id='+rid)[0]==404)
  check('Anonymous report','Anonymous' in student.request('report',{**report,'isAnonymous':'on','contactName':'','contactEmail':''})[1])
  admin.request('dashboard',{'action':'delete-user','id':'5'});check('User deleted','student2@ub.edu.ph' not in admin.request('dashboard')[1])
  check('Logout','page=login' in student.request('dashboard',{'action':'logout'})[2]);check('Logged-out session rejected','page=login' in student.request('dashboard')[2])
 finally:server.terminate();server.wait();log.close()
print('All integration checks passed.')
