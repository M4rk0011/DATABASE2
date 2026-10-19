<?php
require __DIR__.'/../app/bootstrap.php';
if (PHP_SAPI !== 'cli') exit;
$demo=in_array('--demo',$argv,true);
if ($demo) {
 foreach(json_decode(file_get_contents(__DIR__.'/../app/seed-users.json')) as $u)if(!findUser($u->id))saveUser($u,$u->password);
 foreach(json_decode(file_get_contents(__DIR__.'/../app/seed-concerns.json')) as $r)if(!findReport($r->id))saveReport(normalReport($r));
 echo "Demo data installed. Login: admin / admin123, security / security123, student / student123\n";
} else {
 if(users())exit("Users already exist. Use the administrator dashboard to manage them.\n");
 $name=getenv('ADMIN_USERNAME')?:readline('Admin username: ');$email=getenv('ADMIN_EMAIL')?:readline('Admin email: ');$pass=getenv('ADMIN_PASSWORD');
 if(!$pass)exit("Set ADMIN_PASSWORD in your environment, then rerun setup.\n");
 saveUser(obj(['id'=>bin2hex(random_bytes(12)),'username'=>$name,'email'=>$email,'role'=>'admin','profile'=>['firstName'=>'System','lastName'=>'Administrator','phone'=>'','studentId'=>'','department'=>'','position'=>'Campus Security Director'],'createdAt'=>date(DATE_ATOM),'isActive'=>true]),$pass);
 echo "Administrator created.\n";
}
