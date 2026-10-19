<?php
require __DIR__.'/bootstrap.php';
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
if(!users()){header('Location: setup.php');exit;}
$currentUser=isset($_SESSION['user_id'])?findUser($_SESSION['user_id']):null;
$page=is_string($_GET['page']??null)?$_GET['page']:'login';
$page=['incidents'=>'concerns','incident-map'=>'map','report-incident'=>'report'][$page]??$page;
$pages=['login'=>'login','register'=>'register','dashboard'=>'dashboard','concerns'=>'concern-list','details'=>'concern-details','report'=>'report-concern','map'=>'concern-map','home'=>'home','analytics'=>'analytics','sdg-impact'=>'sdg-impact'];
if(!isset($pages[$page])){http_response_code(404);exit('Page not found.');}
$categories=['theft','vandalism','harassment','accident','medical','fire','security','facility','technology','other'];
$severities=['low','medium','high','critical'];$statuses=['reported','investigating','resolved','closed','false_alarm'];$concernStatusOptions=$statuses;
$errorMessage='';$successMessage=$_SESSION['flash']??'';unset($_SESSION['flash']);
require __DIR__.'/actions.php';
if(!$currentUser && !in_array($page,['login','register'],true))go('login');
$allConcerns=reports();$concerns=$allConcerns;
$searchTerm=is_string($_GET['search']??null)?$_GET['search']:'';$statusFilter=is_string($_GET['statusFilter']??null)?$_GET['statusFilter']:'';$severityFilter=is_string($_GET['severityFilter']??null)?$_GET['severityFilter']:'';
$concerns=array_values(array_filter($concerns,fn($r)=>(!$searchTerm||stripos($r->title.' '.$r->description.' '.$r->location->address,$searchTerm)!==false)&&(!$statusFilter||$r->status===$statusFilter)&&(!$severityFilter||$r->severity===$severityFilter)));
$id=is_string($_GET['id']??null)?$_GET['id']:'';$concern=$id?findReport($id):null;$selectedConcern=$page==='map'?$concern:null;
if($page==='details' && !$concern)http_response_code(404);
if($page==='report') { if($id&&!canEditConcernContent($concern)){http_response_code(403);exit('You cannot edit this report.');} }
$isAdmin=isAdmin();$isLoading=$isSubmitting=$showPassword=$showConfirmPassword=false;$mapInitialized=true;$statusPickerOpen=$assignPickerOpen=false;
$users=isAdmin()?users():[];$editingUserId=is_string($_GET['editUser']??null)?$_GET['editUser']:null;
$editUserForm=obj(array_fill_keys(['username','email','password','role','firstName','lastName','phone','studentId','department','position'],''));
if($editingUserId && isAdmin()){ $eu=findUser($editingUserId);if($eu&&!isAdminUser($eu)){foreach(['username','email','role'] as $k)$editUserForm->$k=$eu->$k;foreach($eu->profile as $k=>$v)$editUserForm->$k=$v;}else $editingUserId=null; }
$userActionMessage=$successMessage;$userActionError=$errorMessage;
$loginData=obj(['username'=>post('username'),'password'=>'']);
$registerData=obj(array_fill_keys(['firstName','lastName','username','email','password','confirmPassword','phone','studentId','department'],''));foreach($registerData as $k=>$v)if(!str_contains(strtolower($k),'password'))$registerData->$k=post($k);
$concernData=$concern ? clone $concern : obj(['title'=>'','description'=>'','category'=>'other','severity'=>'medium','location'=>['latitude'=>16.4155555556,'longitude'=>120.5975,'address'=>'','building'=>'','floor'=>'','room'=>'','landmark'=>''],'contactInfo'=>['name'=>trim(($currentUser->profile->firstName??'').' '.($currentUser->profile->lastName??'')),'email'=>$currentUser->email??'','phone'=>$currentUser->profile->phone??'','studentId'=>$currentUser->profile->studentId??'','department'=>$currentUser->profile->department??''],'witnesses'=>[],'additionalNotes'=>'','isAnonymous'=>false]);
if($page==='report' && $_SERVER['REQUEST_METHOD']==='POST'){
 foreach(['title','description','category','severity','additionalNotes'] as $k)$concernData->$k=post($k);
 foreach(['address','building','floor','room','landmark','latitude','longitude'] as $k)$concernData->location->$k=post($k);
 foreach(['name'=>'contactName','email'=>'contactEmail','phone'=>'contactPhone','studentId'=>'studentId','department'=>'department'] as $k=>$v)$concernData->contactInfo->$k=post($v);
 $concernData->isAnonymous=isset($_POST['isAnonymous']);$concernData->witnesses=array_values(array_filter((array)($_POST['witnesses']??[]),'is_string'));
}
$witnessInput='';$recentConcerns=$isAdmin?$allConcerns:array_values(array_filter($allConcerns,fn($r)=>in_array($r->reportedBy,[$currentUser->id??'', $currentUser->username??''],true)));
$stats=obj(['totalConcerns'=>count($recentConcerns),'openConcerns'=>0,'resolvedConcerns'=>0,'criticalConcerns'=>0,'concernsByCategory'=>[],'concernsBySeverity'=>[],'averageResolutionTime'=>0,'concernsThisMonth'=>0]);$times=[];
foreach($recentConcerns as $r){if(in_array($r->status,['reported','investigating']))$stats->openConcerns++;if($r->status==='resolved')$stats->resolvedConcerns++;if($r->severity==='critical')$stats->criticalConcerns++;$stats->concernsByCategory[$r->category]=($stats->concernsByCategory[$r->category]??0)+1;$stats->concernsBySeverity[$r->severity]=($stats->concernsBySeverity[$r->severity]??0)+1;if(date('Y-m',strtotime($r->reportedAt))===date('Y-m'))$stats->concernsThisMonth++;if($r->resolvedAt)$times[]=max(0,(strtotime($r->resolvedAt)-strtotime($r->reportedAt))/3600);}
$stats->averageResolutionTime=$times?array_sum($times)/count($times):0;$recentConcerns=array_slice($recentConcerns,0,5);
$markers=array_map(fn($r)=>(object)['concern'=>$r],$allConcerns);
if(in_array($page,['home','analytics','sdg-impact'],true))require __DIR__.'/legacy.php';
require __DIR__.'/layout.php';
