<?php
require_once __DIR__.'/security.php';require_once __DIR__.'/portal.php';
if($_SERVER['REQUEST_METHOD']==='POST'){adminClearSession();session_destroy();setcookie('SVPORTAL','',['expires'=>time()-3600,'path'=>'/','secure'=>!$adminLocal,'httponly'=>true,'samesite'=>'Strict']);header('Location: /admin/login.php',true,303);exit;}
echo str_replace('class="portal-page"', 'class="portal-page portal-login-page portal-logout-page"', portalDocument('Uitloggen'));
?><header class="admin-header"><a class="admin-brand" href="/admin/"><img src="/admin/assets/logo.svg" alt="Scouting Veghel"><span>Scouting Veghel</span></a></header><main class="portal-login portal-logout"><section aria-labelledby="logout-title"><h1 id="logout-title">Even uitloggen?</h1><form method="post" action="/admin/logout.php"><button>Veilig uitloggen</button></form><p class="portal-logout-back"><a href="/admin/">Ingelogd blijven</a></p></section></main></body></html>
