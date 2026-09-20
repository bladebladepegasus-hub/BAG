<?php
require_once __DIR__.'/comun.php';
require_once __DIR__.'/ia_openai.php';
$_SESSION['csrf'] ??= bin2hex(random_bytes(32));
$profile=perfil();
$cuenta=!empty($_SESSION['autenticado'])?consulta('SELECT correo FROM cuentas WHERE usuario_id=?',[usuario()])->fetch():null;
responder(['success'=>true,'profile'=>$profile,'account'=>['authenticated'=>(bool)$cuenta,'email'=>$cuenta['correo']??null],'csrf'=>$_SESSION['csrf'],'conversation_id'=>$_SESSION['conversacion_id'] ?? null,'ai'=>estado_ia()]);
