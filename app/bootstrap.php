<?php
declare(strict_types=1);
date_default_timezone_set('Asia/Manila');
if (PHP_SAPI !== 'cli') {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Cache-Control: no-store');
}
function db(): PDO {
    static $db;
    if ($db) return $db;
    $configFile = __DIR__.'/../config.php';
    $config = is_file($configFile) ? require $configFile : [];
    $path = getenv('APP_SQLITE_PATH') ?: __DIR__.'/../storage/app.sqlite';
    if (!is_dir(dirname($path))) mkdir(dirname($path), 0700, true);
    $dsn = getenv('DB_DSN') ?: (getenv('APP_SQLITE_PATH') ? 'sqlite:'.$path : ($config['dsn'] ?? 'mysql:host=127.0.0.1;dbname=concern_report_system;charset=utf8mb4'));
    $db = new PDO($dsn, getenv('DB_USER') ?: ($config['user'] ?? 'root'), getenv('DB_PASSWORD') ?: ($config['password'] ?? ''), [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    if (str_starts_with($dsn,'sqlite:')) $db->exec('PRAGMA busy_timeout=5000');
    $db->exec('CREATE TABLE IF NOT EXISTS php_users (id VARCHAR(40) PRIMARY KEY, username VARCHAR(100) NOT NULL UNIQUE, email VARCHAR(200) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, body TEXT NOT NULL)');
    $db->exec('CREATE TABLE IF NOT EXISTS php_reports (id VARCHAR(40) PRIMARY KEY, body TEXT NOT NULL)');
    return $db;
}
function obj(array $a): object { return json_decode(json_encode($a, JSON_THROW_ON_ERROR), false, 512, JSON_THROW_ON_ERROR); }
function e($value): string { return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function url(string $page, array $args=[]): string { return 'index.php?'.http_build_query(['page'=>$page]+$args); }
function go(string $page, array $args=[]): never { header('Location: '.url($page,$args)); exit; }
function users(): array { return array_map(fn($r)=>json_decode($r['body']), db()->query('SELECT body FROM php_users ORDER BY username')->fetchAll()); }
function findUser(string $id): ?object { $s=db()->prepare('SELECT body FROM php_users WHERE id=?');$s->execute([$id]);$v=$s->fetchColumn();return $v ? json_decode($v) : null; }
function saveUser(object $u, ?string $password=null): void {
    $u->username=trim($u->username); $u->email=strtolower(trim($u->email));
    if (!$u->username || strlen($u->username)>100 || !filter_var($u->email,FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Provide a username and valid email address.');
    $s=db()->prepare('SELECT id FROM php_users WHERE (LOWER(username)=LOWER(?) OR LOWER(email)=LOWER(?)) AND id<>?'); $s->execute([$u->username,$u->email,$u->id]);
    if ($s->fetch()) throw new RuntimeException('Username or email already exists.');
    if ($password !== null && strlen($password)<6) throw new RuntimeException('Password must contain at least 6 characters.');
    unset($u->password);
    if (findUser($u->id)) {
        $s=db()->prepare('UPDATE php_users SET username=?,email=?,body=?'.($password!==null?',password_hash=?':'').' WHERE id=?');
        $a=[$u->username,$u->email,json_encode($u,JSON_THROW_ON_ERROR)];if($password!==null)$a[]=password_hash($password,PASSWORD_DEFAULT);$a[]=$u->id;$s->execute($a);
    } else {
        if ($password===null) throw new RuntimeException('Password required.');
        db()->prepare('INSERT INTO php_users (id,username,email,password_hash,body) VALUES (?,?,?,?,?)')->execute([$u->id,$u->username,$u->email,password_hash($password,PASSWORD_DEFAULT),json_encode($u,JSON_THROW_ON_ERROR)]);
    }
}
function reports(): array { $a=array_map(fn($r)=>normalReport(json_decode($r['body'])),db()->query('SELECT body FROM php_reports')->fetchAll());usort($a,fn($a,$b)=>strcmp($b->reportedAt,$a->reportedAt));return $a; }
function normalReport(object $r): object {
    foreach (['resolvedAt','assignedTo','additionalNotes'] as $k) $r->$k ??= '';
    foreach (['building','floor','room','landmark'] as $k) $r->location->$k ??= '';
    foreach (['name','email','phone','studentId','department'] as $k) $r->contactInfo->$k ??= '';
    $r->witnesses ??=[];$r->images ??=[];return $r;
}
function findReport(string $id): ?object { $s=db()->prepare('SELECT body FROM php_reports WHERE id=?');$s->execute([$id]);$v=$s->fetchColumn();return $v?normalReport(json_decode($v)):null; }
function saveReport(object $r): void { if(findReport($r->id))db()->prepare('UPDATE php_reports SET body=? WHERE id=?')->execute([json_encode($r,JSON_THROW_ON_ERROR),$r->id]);else db()->prepare('INSERT INTO php_reports (body,id) VALUES (?,?)')->execute([json_encode($r,JSON_THROW_ON_ERROR),$r->id]); }
function isAdmin(): bool { return ($GLOBALS['currentUser']->role ?? '')==='admin'; }
function canUpdateStatus(): bool { return in_array($GLOBALS['currentUser']->role ?? '',['admin','security'],true); }
function canManageConcerns(): bool { return canUpdateStatus(); }
function canDeleteConcern(): bool { return isAdmin(); }
function canEditConcernContent(?object $r): bool { $u=$GLOBALS['currentUser'];return $r && $u && (canUpdateStatus() || in_array($r->reportedBy,[$u->id,$u->username],true)); }
function canEditConcernRecord(): bool { return canEditConcernContent($GLOBALS['concern']); }
function isAdminUser(object $u): bool { return $u->role==='admin'; }
function demand(bool $ok): void { if(!$ok) throw new RuntimeException('You do not have permission to perform this action.'); }
function csrf_field(): void { echo '<input type="hidden" name="csrf" value="'.e($_SESSION['csrf']).'">'; }
function getSeverityColor(string $s): string { return ['critical'=>'#dc3545','high'=>'#fd7e14','medium'=>'#ffc107','low'=>'#28a745'][$s]??'#6c757d'; }
function getSeverityColorFromKey(string $s): string { return getSeverityColor($s); }
function getStatusColor(string $s): string { return ['reported'=>'#007bff','investigating'=>'#fd7e14','resolved'=>'#28a745','closed'=>'#6c757d','false_alarm'=>'#6f42c1'][$s]??'#6c757d'; }
function getCategoryDisplayName(string $s): string { return strtoupper(str_replace('_',' ',$s)); }
function getSeverityDisplayName(string $s): string { return strtoupper($s); }
function formatStatusLabel(string $s): string { return str_replace('_',' ',$s); }
function getSeverityInitial(string $s): string { return strtoupper(substr($s,0,1)); }
function fmtdate($s): string { return $s?date('M j, Y, g:i A',strtotime($s)):''; }
function getTimeElapsed(string $s): string { $h=max(0,(int)((time()-strtotime($s))/3600));return $h<1?'Less than 1 hour':($h<24?$h.' hours':floor($h/24).' days'); }
function pairs($a): array { $out=[];foreach($a as $k=>$v)$out[]=(object)['key'=>$k,'value'=>$v];return $out; }
function getNewReportsCount(): int { return count(array_filter($GLOBALS['allConcerns'],fn($r)=>$r->status==='reported')); }
function getCriticalCount(): int { return count(array_filter($GLOBALS['allConcerns'],fn($r)=>$r->severity==='critical')); }
function getResolvedCount(): int { return count(array_filter($GLOBALS['allConcerns'],fn($r)=>$r->status==='resolved')); }
function post(string $key): string { return is_string($_POST[$key]??null)?trim($_POST[$key]):''; }
