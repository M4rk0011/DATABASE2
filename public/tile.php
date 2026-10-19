<?php
// On-demand, same-origin campus map tiles. Fixed upstream hosts; no arbitrary URLs.
require __DIR__.'/../app/bootstrap.php';
if(!isset($_SESSION['user_id']) || !findUser((string)$_SESSION['user_id'])){http_response_code(401);exit;}
session_write_close();
$z=filter_input(INPUT_GET,'z',FILTER_VALIDATE_INT);$x=filter_input(INPUT_GET,'x',FILTER_VALIDATE_INT);$y=filter_input(INPUT_GET,'y',FILTER_VALIDATE_INT);
$provider=is_string($_GET['provider']??null)?$_GET['provider']:'street';
if(!in_array($provider,['street','alternate','satellite'],true)||$z===false||$z===null||$z<16||$z>19||$x===false||$x===null||$y===false||$y===null){http_response_code(400);exit;}
$n=2**$z;$west=(int)floor((120.595925+180)/360*$n)-3;$east=(int)floor((120.599075+180)/360*$n)+3;
$tileY=fn($lat)=>(int)floor((1-asinh(tan(deg2rad($lat)))/M_PI)/2*$n);
if($x<$west||$x>$east||$y<$tileY(16.416568)-3||$y>$tileY(16.414543)+3){http_response_code(400);exit;}
$cache=__DIR__.'/../storage/map-cache';if(!is_dir($cache))mkdir($cache,0700,true);
$file="$cache/$provider-$z-$x-$y.tile";$mime=$provider==='satellite'?'image/jpeg':'image/png';
if(is_file($file)&&filemtime($file)>time()-604800){header('Content-Type: '.$mime);header('Cache-Control: private, max-age=604800');readfile($file);exit;}
$url=match($provider){'street'=>"https://tile.openstreetmap.org/$z/$x/$y.png",'alternate'=>"https://basemaps.cartocdn.com/light_all/$z/$x/$y.png",'satellite'=>"https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/$z/$y/$x"};
$agent='UB-Concern-Report-System/2.0 (campus reporting map; on-demand tiles)';$bytes=false;
if(function_exists('curl_init')){
 $h=curl_init($url);curl_setopt_array($h,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>4,CURLOPT_TIMEOUT=>8,CURLOPT_USERAGENT=>$agent,CURLOPT_FOLLOWLOCATION=>false]);$bytes=curl_exec($h);$code=curl_getinfo($h,CURLINFO_RESPONSE_CODE);curl_close($h);if($code!==200)$bytes=false;
}else{
 $ctx=stream_context_create(['http'=>['timeout'=>8,'header'=>"User-Agent: $agent\r\nAccept: image/*\r\n"],'ssl'=>['verify_peer'=>true,'verify_peer_name'=>true]]);$bytes=@file_get_contents($url,false,$ctx,0,1000000);
}
$valid=is_string($bytes)&&strlen($bytes)<1000000&&($mime==='image/png'?str_starts_with($bytes,"\x89PNG\r\n\x1a\n"):str_starts_with($bytes,"\xff\xd8"));
if(!$valid){http_response_code(502);header('Cache-Control: no-store');exit;}
file_put_contents($file,$bytes,LOCK_EX);header('Content-Type: '.$mime);header('Cache-Control: private, max-age=604800');echo $bytes;
