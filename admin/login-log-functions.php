<?php
function portalLoginRecord($result,$username,$return,$method='password') {
    if(!in_array($result,['success','bad_password','mfa_required','bad_mfa','rate_limited','error'],true))return;
    $username=is_string($username)?substr(preg_replace('/[\x00-\x1f\x7f]/','',$username),0,133):'';
    $ip=$_SERVER['REMOTE_ADDR']??'';if(!filter_var($ip,FILTER_VALIDATE_IP))$ip='';
    $portal=strpos($return,'/leden/')===0?'leden':((strpos($return,'/beheer/')===0 || strpos($return,'/verhuur/')===0)?'beheer':'admin');
    try {
        $db=siteDatabaseConnection();
        $db->query('DELETE FROM portal_login_log WHERE created_at < NOW() - INTERVAL 30 DAY');
        $st=$db->prepare('INSERT INTO portal_login_log(username,ip_address,result,method,portal) VALUES (?,?,?,?,?)');
        $st->bind_param('sssss',$username,$ip,$result,$method,$portal);$st->execute();$db->close();
    }catch(Throwable $e){error_log('Portaal: inlogpoging kon niet in de historie worden vastgelegd.');}
}
