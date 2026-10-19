<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>University of Baguio Concern Report System</title>
<link rel="stylesheet" href="css/styles.css"><link rel="stylesheet" href="css/enhancements.css"><link rel="stylesheet" href="css/shell.css"><link rel="stylesheet" href="css/<?= e($pages[$page]) ?>.css">
<link rel="stylesheet" href="vendor/leaflet/leaflet.css"><link rel="stylesheet" href="css/redesign.css?v=3">
<style>[hidden]{display:none!important} [data-href]{cursor:pointer} .app-message{margin:1rem;padding:1rem;border:1px solid #198754;background:#eefaf3;color:#16442c}.app-error{border-color:#dc3545;background:#fff2f2;color:#842029}@media print{.main-nav,.page-header,.header-actions,.quick-actions,.status-actions,.app-message{display:none!important}.concern-content{display:block!important}.sidebar{display:none!important}body{background:white!important}}@media(max-width:480px){.page-dashboard .stats-grid,.page-dashboard .actions-grid{grid-template-columns:minmax(0,1fr)}.page-dashboard .section-header{flex-wrap:wrap;gap:12px}.page-dashboard .user-main{min-width:0;overflow-wrap:anywhere}}
</style>
</head><body class="ui-redesign <?= in_array($page,['login','register'],true)?'auth-page':'portal-page' ?>">
<div class="app-container" data-ui-shell>
<?php if($currentUser && !in_array($page,['login','register'],true)): ?>
<nav class="main-nav" data-ui-shell><div class="nav-brand" data-ui-shell><span class="brand-mark">UB</span><h1 data-ui-shell>University of Baguio</h1><span class="nav-subtitle" data-ui-shell>Concern Report System</span></div>
<div class="nav-section-label">WORKSPACE</div><div class="nav-menu" data-ui-shell>
<?php foreach(['dashboard'=>'Dashboard','concerns'=>'Concern reports','map'=>'Campus map','report'=>'New concern'] as $p=>$label):  ?>
<a data-ui-shell href="<?= e(url($p)) ?>" class="nav-link <?= $page===$p?'active':'' ?>" <?= $page===$p?'aria-current="page"':'' ?>><span class="nav-glyph" aria-hidden="true"><?= ['dashboard'=>'◫','concerns'=>'▤','map'=>'⌖','report'=>'+'][$p] ?></span><?= e($label) ?></a>
<?php endforeach; ?></div>
<div class="nav-user" data-ui-shell><div class="user-info" data-ui-shell><span class="user-name" data-ui-shell><?= e($currentUser->profile->firstName.' '.$currentUser->profile->lastName) ?></span><span class="user-role" data-ui-shell><?= e(strtoupper($currentUser->role)) ?></span></div><form method="post"><?php csrf_field(); ?><button name="action" value="logout" class="logout-btn" data-ui-shell>Logout</button></form></div></nav>
<?php endif; ?>
<main class="main-content <?= $currentUser?'authenticated':'' ?>" data-ui-shell>
<?php if($successMessage): ?><div class="app-message" role="status"><?= e($successMessage) ?></div><?php endif; ?>
<?php if($errorMessage): ?><div class="app-message app-error" role="alert"><?= e($errorMessage) ?></div><?php endif; ?>
<div class="page-<?= e($page) ?>"><?php require __DIR__.'/views/'.$page.'.php'; ?></div>
</main></div><footer></footer>
<script type="application/json" id="app-data"><?= json_encode(['page'=>$page,'csrf'=>$_SESSION['csrf'],'reports'=>array_map(function($r){return ['id'=>$r->id,'title'=>$r->title,'severity'=>$r->severity,'location'=>['latitude'=>$r->location->latitude,'longitude'=>$r->location->longitude]];},$page==='map'?$allConcerns:[]),'selected'=>$selectedConcern->id??null], JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT) ?></script>
<script src="vendor/leaflet/leaflet.js"></script>
<script src="app.js?v=2" defer></script></body></html>
