<?php
// Render shared legacy modules as full pages, without changing downloads or JSON responses.
require_once __DIR__.'/security.php';adminRequireLogin();require_once __DIR__.'/portal.php';
if(!isset($portalModuleHeader)) {
    ob_start();portalHeader('admin',$portalModule??'');$portalModuleHeader=ob_get_clean();
}
ob_start(static function($html) use($portalModuleHeader) {
    if(!preg_match('/^\s*(?:<!doctype\s+html|<html\b)/i',$html))return $html;
    $html=preg_replace('~</head>~i','<link rel="stylesheet" href="/admin/portal.css?v=20261101-layoutlog"></head>',$html,1);
    return preg_replace_callback('/<body\b([^>]*)>/i',static function($match) use($portalModuleHeader) {
        $attributes=$match[1];
        if(preg_match('/\bclass\s*=\s*([\x22\x27])(.*?)\1/i',$attributes)) {
            $attributes=preg_replace('/\bclass\s*=\s*([\x22\x27])(.*?)\1/i','class="$2 portal-page portal-admin-workspace"',$attributes,1);
        } else $attributes.=' class="portal-page portal-admin-workspace"';
        return '<body'.$attributes.'>'.$portalModuleHeader;
    },$html,1);
});
