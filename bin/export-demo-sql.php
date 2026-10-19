<?php
require __DIR__.'/../app/bootstrap.php';
if(PHP_SAPI!=='cli')exit;
function quoteSql(string $s):string{return "'".str_replace(["\\","'"],["\\\\","''"],$s)."'";}
$sql="-- Concern Report System: XAMPP / MySQL / MariaDB\n-- Import in phpMyAdmin. Existing accounts/reports are not overwritten.\nCREATE DATABASE IF NOT EXISTS concern_report_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;\nUSE concern_report_system;\nCREATE TABLE IF NOT EXISTS php_users (id VARCHAR(40) PRIMARY KEY, username VARCHAR(100) NOT NULL UNIQUE, email VARCHAR(200) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, body TEXT NOT NULL);\nCREATE TABLE IF NOT EXISTS php_reports (id VARCHAR(40) PRIMARY KEY, body TEXT NOT NULL);\n";
foreach(json_decode(file_get_contents(__DIR__.'/../app/seed-users.json')) as $u){$hash=password_hash($u->password,PASSWORD_DEFAULT);unset($u->password);$sql.='INSERT IGNORE INTO php_users (id,username,email,password_hash,body) VALUES ('.implode(',',array_map('quoteSql',[$u->id,$u->username,$u->email,$hash,json_encode($u,JSON_UNESCAPED_UNICODE)])). ");\n";}
foreach(json_decode(file_get_contents(__DIR__.'/../app/seed-concerns.json')) as $r)$sql.='INSERT IGNORE INTO php_reports (id,body) VALUES ('.quoteSql($r->id).','.quoteSql(json_encode(normalReport($r),JSON_UNESCAPED_UNICODE)).");\n";
file_put_contents(__DIR__.'/../database.sql',$sql);echo "database.sql generated.\n";
