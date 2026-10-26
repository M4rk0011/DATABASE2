<?php
// Attachments live outside public/ and are only served to authenticated users.
function photoDirectory(): string { return getenv('APP_UPLOAD_PATH') ?: __DIR__.'/../storage/uploads'; }
function validPhotoName($name): bool { return is_string($name) && preg_match('/\A[a-f0-9]{32}\.(jpg|png|webp)\z/D', $name) === 1; }
function photoUrl(string $reportId, string $name): string { return 'photo.php?'.http_build_query(['report'=>$reportId,'name'=>$name]); }
function deletePhoto($name): void { if(validPhotoName($name) && is_file(photoDirectory().'/'.$name)) @unlink(photoDirectory().'/'.$name); }
function saveReportWithPhotos(object $r): void {
    $old=$r->images ?? [];
    $remove=$_POST['removePhotos'] ?? [];
    if(!is_array($remove) || array_filter($remove,fn($v)=>!is_string($v))) throw new RuntimeException('Invalid photo selection.');
    $remove=array_values(array_intersect($old,$remove));
    $keep=array_values(array_diff($old,$remove));
    $files=$_FILES['photos'] ?? null;$pending=[];
    if($files!==null) {
        if(!is_array($files['error'] ?? null)) throw new RuntimeException('Invalid photo upload.');
        foreach($files['error'] as $i=>$error) {
            if($error===UPLOAD_ERR_NO_FILE)continue;
            if($error!==UPLOAD_ERR_OK)throw new RuntimeException('Photo upload failed. Maximum 2 MB per photo; check upload_max_filesize in php.ini.');
            $tmp=$files['tmp_name'][$i] ?? '';
            if(!is_string($tmp)||!is_uploaded_file($tmp))throw new RuntimeException('Invalid photo upload.');
            if(filesize($tmp)>2*1024*1024)throw new RuntimeException('Each photo must be 2 MB or smaller.');
            $mime=(new finfo(FILEINFO_MIME_TYPE))->file($tmp);
            $ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime] ?? null;
            $info=@getimagesize($tmp);
            if(!$ext || !$info || $info['mime']!==$mime)throw new RuntimeException('Only valid JPG, PNG and WebP images are accepted.');
            if($info[0]*$info[1]>20000000)throw new RuntimeException('Photo resolution is too large. Use an image under 20 megapixels.');
            $pending[]=['tmp'=>$tmp,'name'=>bin2hex(random_bytes(16)).'.'.$ext];
        }
    }
    if(count($keep)+count($pending)>5)throw new RuntimeException('Maximum 5 photos per report. Remove an existing photo before adding more.');
    $saved=[];
    try {
        if($pending && !is_dir(photoDirectory()) && !mkdir(photoDirectory(),0700,true))throw new RuntimeException('Cannot create the photo folder. Check storage folder permissions.');
        foreach($pending as $file) {
            if(!move_uploaded_file($file['tmp'],photoDirectory().'/'.$file['name']))throw new RuntimeException('Cannot save the photo. Check storage folder permissions.');
            $saved[]=$file['name'];
        }
        $r->images=array_merge($keep,$saved);saveReport($r);
    } catch(Throwable $ex) { foreach($saved as $name)deletePhoto($name);throw $ex; }
    foreach($remove as $name)deletePhoto($name);
}
