<?php
if(PHP_SAPI!=='cli')exit;
require_once __DIR__.'/catalogo_online.php';
$c=curl_init('https://static.eneba.games/main.a5b831c49fe25d9fe643.bundle.js');curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_TIMEOUT=>25,CURLOPT_USERAGENT=>'Mozilla/5.0']);$js=curl_exec($c);
preg_match('/\{AF:1,[^}]+MX:140[^}]+\}/',$js,$m);echo ($m[0]??'map not found')."\n";

