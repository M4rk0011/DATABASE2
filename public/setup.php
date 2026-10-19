<?php
require __DIR__.'/../app/bootstrap.php';
// Initial installation is available only on the XAMPP computer itself.
if(!in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true)){http_response_code(403);exit('Run initial setup on localhost on the XAMPP computer.');}
$_SESSION['csrf']??=bin2hex(random_bytes(32));$error='';$ready=false;
try{$ready=count(users())>0;}catch(PDOException $e){/* Database may not exist yet. */}
if($_SERVER['REQUEST_METHOD']==='POST'&&!$ready){
 try{
  if(!hash_equals($_SESSION['csrf'],(string)($_POST['csrf']??'')))throw new RuntimeException('Reload the setup page and try again.');
  $config=is_file(__DIR__.'/../config.php')?require __DIR__.'/../config.php':[];
  $dsn=getenv('DB_DSN')?:($config['dsn']??'mysql:host=127.0.0.1;dbname=concern_report_system;charset=utf8mb4');
  if(str_starts_with($dsn,'mysql:')){
   if(!preg_match('/(?:^|;)dbname=([A-Za-z0-9_]+)/',$dsn,$m))throw new RuntimeException('The database name in config.php is invalid.');
   $serverDsn=preg_replace('/;dbname=[^;]+/','',$dsn);
   $pdo=new PDO($serverDsn,getenv('DB_USER')?:($config['user']??'root'),getenv('DB_PASSWORD')?:($config['password']??''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
   $pdo->exec('CREATE DATABASE IF NOT EXISTS `'.$m[1].'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
  }
  if(users())throw new RuntimeException('Setup is already complete.');
  if(isset($_POST['demo'])){
   foreach(json_decode(file_get_contents(__DIR__.'/../app/seed-users.json')) as $u)saveUser($u,$u->password);
   foreach(json_decode(file_get_contents(__DIR__.'/../app/seed-concerns.json')) as $r)saveReport(normalReport($r));
  }else{
   if(!trim($_POST['username']??'')||!trim($_POST['email']??''))throw new RuntimeException('Provide an administrator username and email.');
   saveUser(obj(['id'=>bin2hex(random_bytes(12)),'username'=>trim($_POST['username']),'email'=>trim($_POST['email']),'role'=>'admin','profile'=>['firstName'=>'System','lastName'=>'Administrator','phone'=>'','studentId'=>'','department'=>'','position'=>'Administrator'],'createdAt'=>date(DATE_ATOM),'isActive'=>true]),$_POST['password']??'');
  }
  $ready=true;
 }catch(PDOException $e){$error='Cannot connect to MySQL. Start MySQL in XAMPP. If you changed its port or password, copy config.example.php to config.php and enter those settings.';}catch(RuntimeException $e){$error=$e->getMessage();}
}
?><!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Setup — Concern Report System</title><link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/enhancements.css"><link rel="stylesheet" href="css/redesign.css?v=3"></head><body class="ui-redesign auth-page"><main class="setup-card"><h1>Concern Report System</h1><h2>XAMPP / MySQL setup</h2>
<?php if($ready): ?><p class="success">Setup complete. Your accounts and concern reports are saved in the database.</p><p>Demo accounts, if installed: admin / admin123, student / student123, security / security123.</p><a class="btn btn-primary" href="index.php?page=login">Open login</a>
<?php else: ?><p>Start Apache and MySQL in XAMPP, then initialize the database here. This keeps existing records and does not require XAMPP Shell.</p><?php if($error): ?><p role="alert" class="error"><?= e($error) ?></p><?php endif; ?><form method="post"><?php csrf_field(); ?><label><input style="width:auto;display:inline" type="checkbox" name="demo" checked> Install original demo accounts and sample reports</label><p>For a fresh system without demo data, uncheck that option and enter your administrator details:</p><label>Administrator username<input name="username" autocomplete="username"></label><label>Email<input name="email" type="email"></label><label>Password (6+ characters)<input name="password" type="password" autocomplete="new-password" minlength="6"></label><button type="submit">Initialize database</button></form><?php endif; ?></main></body></html>
