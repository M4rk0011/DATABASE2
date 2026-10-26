<?php
if ($_SERVER['REQUEST_METHOD']==='POST') {
 try {
    if (empty($_POST) && (int)($_SERVER['CONTENT_LENGTH'] ?? 0)>0) throw new RuntimeException('Upload exceeds the server request limit. Use photos under 2 MB each and set post_max_size to 16M in php.ini.');
    if (!hash_equals($_SESSION['csrf'],post('csrf'))) { http_response_code(403);throw new RuntimeException('Session expired. Reload this page and try again.'); }
    $action=post('action');
    if($action==='login') {
        $s=db()->prepare('SELECT * FROM php_users WHERE LOWER(username)=LOWER(?)');$s->execute([post('username')]);$row=$s->fetch();
        if(!$row || !password_verify($_POST['password']??'', $row['password_hash'])) throw new RuntimeException('Invalid username or password.');
        $u=json_decode($row['body']);if(!$u->isActive)throw new RuntimeException('Account is inactive.');
        session_regenerate_id(true);$_SESSION['user_id']=$u->id;$_SESSION['csrf']=bin2hex(random_bytes(32));$u->lastLogin=date(DATE_ATOM);saveUser($u);go('dashboard');
    }
    if($action==='register') {
        if(!post('firstName') || !post('lastName'))throw new RuntimeException('First and last name are required.');
        if(($_POST['password']??'')!==($_POST['confirmPassword']??''))throw new RuntimeException('Passwords do not match.');
        $u=obj(['id'=>bin2hex(random_bytes(12)),'username'=>post('username'),'email'=>post('email'),'role'=>'user','profile'=>array_fill_keys(['firstName','lastName','phone','studentId','department','position'],''),'createdAt'=>date(DATE_ATOM),'lastLogin'=>null,'isActive'=>true]);
        foreach($u->profile as $k=>$v)$u->profile->$k=post($k);
        saveUser($u,$_POST['password']??'');$_SESSION['flash']='Account created. Please log in.';go('login');
    }
    demand((bool)$currentUser);
    if($action==='logout'){$_SESSION=[];session_regenerate_id(true);go('login');}
    if($action==='create-user') {
        demand(isAdmin());demand(in_array(post('role'),['user','security'],true));
        if(!post('firstName')||!post('lastName'))throw new RuntimeException('First and last name are required.');
        $u=obj(['id'=>bin2hex(random_bytes(12)),'username'=>post('username'),'email'=>post('email'),'role'=>post('role'),'profile'=>['firstName'=>post('firstName'),'lastName'=>post('lastName'),'phone'=>post('phone'),'studentId'=>post('studentId'),'department'=>post('department'),'position'=>''],'createdAt'=>date(DATE_ATOM),'isActive'=>true]);
        saveUser($u,$_POST['password']??'');$_SESSION['flash']='User created successfully.';go('dashboard');
    }
    if(in_array($action,['save-user','delete-user'],true)) {
        demand(isAdmin());$u=findUser(post('id'));demand($u!==null && !isAdminUser($u));
        if($action==='delete-user'){db()->prepare('DELETE FROM php_users WHERE id=?')->execute([$u->id]);}
        else {
            demand(in_array(post('role'),['user','security'],true));$u->username=post('username');$u->email=post('email');$u->role=post('role');
            foreach(['firstName','lastName','phone','studentId','department','position'] as $k)$u->profile->$k=post($k);
            saveUser($u,($_POST['password']??'')!==''?$_POST['password']:null);
        }
        $_SESSION['flash']='User '.($action==='delete-user'?'deleted':'updated').' successfully.';go('dashboard');
    }
    if($action==='save-report') {
        $r=post('id')?findReport(post('id')):null;
        if(post('id'))demand(canEditConcernContent($r));else demand((bool)$currentUser);
        foreach(['title','description','address'] as $k)if(!post($k))throw new RuntimeException(ucfirst($k).' is required.');
        if(!in_array(post('category'),$categories,true)||!in_array(post('severity'),$severities,true))throw new RuntimeException('Invalid category or severity.');
        $lat=filter_var(post('latitude'),FILTER_VALIDATE_FLOAT);$lng=filter_var(post('longitude'),FILTER_VALIDATE_FLOAT);
        if($lat===false||$lng===false||$lat<16.414543||$lat>16.416568||$lng<120.595925||$lng>120.599075)throw new RuntimeException('Please place the pin inside the campus boundary.');
        $anon=isset($_POST['isAnonymous']);
        if(!$anon && (!post('contactName')||!filter_var(post('contactEmail'),FILTER_VALIDATE_EMAIL)))throw new RuntimeException('A contact name and valid email are required.');
        $r ??= obj(['id'=>bin2hex(random_bytes(8)),'status'=>'reported','reportedBy'=>$currentUser->id,'reportedAt'=>date(DATE_ATOM),'resolvedAt'=>'','assignedTo'=>'','images'=>[]]);
        foreach(['title','description','category','severity','additionalNotes'] as $k)$r->$k=post($k);
        $r->location=obj(['latitude'=>$lat,'longitude'=>$lng]);foreach(['address','building','floor','room','landmark'] as $k)$r->location->$k=post($k);
        $r->contactInfo=obj(['name'=>$anon?'Anonymous':post('contactName'),'email'=>$anon?'anonymous@ub.edu.ph':post('contactEmail'),'phone'=>$anon?'':post('contactPhone'),'studentId'=>$anon?'':post('studentId'),'department'=>$anon?'':post('department')]);
        $w=$_POST['witnesses']??[];if(!is_array($w))throw new RuntimeException('Invalid witnesses.');
        $r->witnesses=array_values(array_filter(array_map(fn($s)=>is_string($s)?trim($s):'',array_slice($w,0,100))));$r->isAnonymous=$anon;$r->updatedAt=date(DATE_ATOM);saveReportWithPhotos($r);
        $_SESSION['flash']='Report saved successfully.';go('details',['id'=>$r->id]);
    }
    $r=findReport(post('id'));if(!$r)throw new RuntimeException('Report not found.');
    if($action==='delete-report') {demand(isAdmin());db()->prepare('DELETE FROM php_reports WHERE id=?')->execute([$r->id]);foreach($r->images as $photo)deletePhoto($photo);go('concerns');}
    if($action==='status') {
        demand(canUpdateStatus());if(!in_array(post('value'),$statuses,true))throw new RuntimeException('Invalid status.');
        $r->status=post('value');if($r->status==='resolved')$r->resolvedAt=date(DATE_ATOM);elseif($r->status!=='closed')$r->resolvedAt='';
    } elseif($action==='assign') {
        demand(canUpdateStatus());if(!in_array(post('value'),['security','admin','facilities','medical'],true))throw new RuntimeException('Invalid staff assignment.');$r->assignedTo=post('value');
    } else throw new RuntimeException('Unknown action.');
    $r->updatedAt=date(DATE_ATOM);saveReport($r);$_SESSION['flash']='Report updated.';go($page==='map'?'map':'details',['id'=>$r->id]);
 } catch (RuntimeException $ex) { $errorMessage=$ex->getMessage(); }
}
