<?php
// Run: php -S localhost:8000 -t public public/router.php
$path=parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH);
if(is_file(__DIR__.$path) && $path!=='/router.php')return false;
if($path==='/' || $path==='/index.php'){require __DIR__.'/index.php';return;}
if(preg_match('~^/(concerns|incidents)/([^/]+)$~',$path,$m)){$_GET['page']='details';$_GET['id']=$m[2];require __DIR__.'/index.php';return;}
if(preg_match('~^/(login|register|dashboard|map|report|concerns|incidents|home|analytics|sdg-impact)/?$~',$path,$m)){header('Location: /index.php?page='.$m[1]);return;}
http_response_code(404);echo 'Not found';
