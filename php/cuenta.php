<?php
require_once __DIR__.'/comun.php';
$d=entrada();$accion=texto($d['accion']??'',20);
function renovar_acceso(?int $uid):void {
    $_SESSION=[];
    session_regenerate_id(true);
    if($uid!==null){$_SESSION['usuario_id']=$uid;$_SESSION['autenticado']=$uid;}
    $_SESSION['csrf']=bin2hex(random_bytes(32));
}
function limitar_acceso(string $clave,int $max):void {
    $clave=hash('sha256',$clave);
    consulta("INSERT INTO acceso_limites(clave,intentos) VALUES (?,1) ON DUPLICATE KEY UPDATE intentos=IF(inicio < DATE_SUB(NOW(),INTERVAL 15 MINUTE),1,intentos+1),inicio=IF(inicio < DATE_SUB(NOW(),INTERVAL 15 MINUTE),NOW(),inicio)",[$clave]);
    if((int)consulta('SELECT intentos FROM acceso_limites WHERE clave=?',[$clave])->fetchColumn()>$max){header('Retry-After: 900');responder(['success'=>false,'message'=>'Demasiados intentos. Espera 15 minutos antes de volver a intentarlo.'],429);}
}
if($accion==='salir'){renovar_acceso(null);responder(['success'=>true,'message'=>'Sesión cerrada.']);}
if(!in_array($accion,['registrar','entrar'],true))throw new InvalidArgumentException('Acción no válida.');
if(!empty($_SESSION['autenticado']))responder(['success'=>false,'message'=>'Ya tienes una sesión iniciada.'],409);
$correo=strtolower(texto($d['correo']??'',254));
if(!filter_var($correo,FILTER_VALIDATE_EMAIL)||preg_match('/[^\x20-\x7e]/',$correo))throw new InvalidArgumentException('Escribe un correo electrónico válido.');
$password=$d['password']??'';
if(!is_string($password)||strlen($password)>72||str_contains($password,"\0")||strlen($password)<1)throw new InvalidArgumentException('Revisa tu contraseña (máximo 72 bytes).');
$ip=$_SERVER['REMOTE_ADDR']??'local';
limitar_acceso('ip:'.$ip,40);
limitar_acceso($accion.':'.$correo,10);
if($accion==='registrar'){
    if(mb_strlen($password)<10)throw new InvalidArgumentException('Usa una contraseña de al menos 10 caracteres.');
    $nombre=texto($d['nombre']??'',80);
    if($nombre==='')throw new InvalidArgumentException('Escribe tu nombre o apodo.');
    $config=validar_configuracion($d,perfil());
    $uid=usuario();$db=conexion();$db->beginTransaction();
    try{
        consulta('INSERT INTO cuentas(usuario_id,correo,password_hash) VALUES (?,?,?)',[$uid,$correo,password_hash($password,PASSWORD_DEFAULT)]);
        consulta('UPDATE usuarios SET nombre=? WHERE id=?',[$nombre,$uid]);
        guardar_configuracion($uid,$config);
        $db->commit();
    }catch(PDOException $e){if($db->inTransaction())$db->rollBack();if($e->getCode()==='23000')responder(['success'=>false,'message'=>'No se pudo crear la cuenta con ese correo. Si ya tienes una, inicia sesión.'],409);throw $e;}
    renovar_acceso($uid);
    responder(['success'=>true,'message'=>'Cuenta creada. Tu historial y tus preferencias se conservaron.']);
}
$cuenta=consulta('SELECT usuario_id,password_hash FROM cuentas WHERE correo=?',[$correo])->fetch();
$hash=$cuenta['password_hash']??'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2uheWG/igi.';
if(!password_verify($password,$hash)||!$cuenta)responder(['success'=>false,'message'=>'Correo o contraseña incorrectos.'],401);
$uid=(int)$cuenta['usuario_id'];
if(password_needs_rehash($hash,PASSWORD_DEFAULT))consulta('UPDATE cuentas SET password_hash=? WHERE usuario_id=?',[password_hash($password,PASSWORD_DEFAULT),$uid]);
renovar_acceso($uid);
responder(['success'=>true,'message'=>'Sesión iniciada.']);
