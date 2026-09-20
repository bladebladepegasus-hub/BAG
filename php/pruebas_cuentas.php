<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/conexion.php';
$total=0;$fallos=0;$jars=[];$tokens=[];$uids=[];$marca='BAG_AUTH_'.bin2hex(random_bytes(5));
function req_cuenta(string $sesion,string $ruta,?array $datos=null,?string $token=null):array{
    global $jars,$tokens;
    $jars[$sesion]??=tempnam(sys_get_temp_dir(),'bag_auth_');
    $c=curl_init('http://localhost/BAG/php/'.$ruta);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$jars[$sesion],CURLOPT_COOKIEJAR=>$jars[$sesion],CURLOPT_TIMEOUT=>20]);
    if($datos!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($datos),CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-CSRF-Token: '.($token??$tokens[$sesion]??'')]]);
    $raw=curl_exec($c);$code=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);
    $json=json_decode($raw,true)??[];if(isset($json['csrf']))$tokens[$sesion]=$json['csrf'];
    return ['code'=>$code,'data'=>$json];
}
function chk(bool $ok,string $label):void{global $total,$fallos;$total++;if(!$ok)$fallos++;echo ($ok?'OK ':'FAIL ').$label.PHP_EOL;}
try{
    $a=req_cuenta('a','preferencias.php');chk($a['data']['account']['authenticated']===false,'Modo invitado disponible');
    req_cuenta('a','guardar_preferencias.php',['nombre'=>$marca,'clasificacion_maxima'=>'B','plataforma'=>'Xbox','genero'=>'','modo_juego'=>'Ambos']);
    $id=req_cuenta('a','nueva_conversacion.php',[])['data']['conversation_id'];
    $s=conexion()->prepare('SELECT usuario_id FROM conversaciones WHERE id=?');$s->execute([$id]);$uids[]=$uid=(int)$s->fetchColumn();
    $s=conexion()->prepare('INSERT INTO mensajes(conversacion_id,emisor,mensaje) VALUES (?,?,?)');$s->execute([$id,'usuario','Historial conservado al registrarse']);
    $correo=strtolower($marca).'@example.test';$password='Prueba-segura-'.bin2hex(random_bytes(8));$antes=$tokens['a'];
    $r=req_cuenta('a','cuenta.php',['accion'=>'registrar','nombre'=>$marca,'correo'=>$correo,'password'=>$password]);chk($r['code']===200,'Registro correcto');
    $a=req_cuenta('a','preferencias.php');chk($a['data']['account']['authenticated']===true,'Registro inicia sesión');
    chk($tokens['a']!==$antes,'Token CSRF rotado al registrar');
    chk($a['data']['profile']['clasificacion_maxima']==='B','Registro conserva preferencias del invitado');
    chk(count(req_cuenta('a','historial.php?id='.$id)['data']['messages'])===1,'Registro conserva mensajes');
    $s=conexion()->prepare('SELECT password_hash FROM cuentas WHERE usuario_id=?');$s->execute([$uid]);$hash=$s->fetchColumn();chk($hash!==$password&&password_verify($password,$hash),'Contraseña almacenada como hash');
    req_cuenta('b','preferencias.php');
    $r=req_cuenta('b','cuenta.php',['accion'=>'registrar','nombre'=>$marca.'b','correo'=>$correo,'password'=>$password]);chk($r['code']===409,'Correo duplicado rechazado');
    $r=req_cuenta('b','cuenta.php',['accion'=>'entrar','correo'=>$correo,'password'=>'incorrecta']);chk($r['code']===401,'Contraseña incorrecta rechazada');
    chk(req_cuenta('b','historial.php?id='.$id)['code']===404,'Otro invitado no accede al historial');
    $r=req_cuenta('b','cuenta.php',['accion'=>'entrar','correo'=>$correo,'password'=>$password],'token-falso');chk($r['code']===403,'Inicio de sesión protege CSRF');
    $r=req_cuenta('b','cuenta.php',['accion'=>'entrar','correo'=>$correo,'password'=>$password]);chk($r['code']===200,'Inicio desde otra sesión');
    $b=req_cuenta('b','preferencias.php');chk($b['data']['profile']['clasificacion_maxima']==='B','Otra sesión recupera preferencias');
    chk(req_cuenta('b','historial.php?id='.$id)['code']===200,'Otra sesión recupera historial de su cuenta');
    chk(req_cuenta('b','cuenta.php',['accion'=>'salir'])['code']===200,'Cierre de sesión correcto');
    $b=req_cuenta('b','preferencias.php');chk(!$b['data']['account']['authenticated'],'Cierre vuelve a invitado');
    chk(req_cuenta('b','historial.php?id='.$id)['code']===404,'Después de salir no queda acceso al historial privado');
    chk(req_cuenta('a','historial.php?id='.$id)['code']===200,'Cerrar otra sesión no borra datos de cuenta');
    for($i=0;$i<11;$i++)$r=req_cuenta('b','cuenta.php',['accion'=>'entrar','correo'=>strtolower($marca).'_limit@example.test','password'=>'incorrecta']);
    chk($r['code']===429,'Intentos repetidos limitados en servidor');
}finally{
    foreach($uids as $uid){$s=conexion()->prepare('DELETE FROM usuarios WHERE id=? AND nombre=?');$s->execute([$uid,$marca]);}
    // Sólo buckets creados por estas pruebas; no se eliminan límites de otras personas.
    foreach(['registrar:'.strtolower($marca).'@example.test','entrar:'.strtolower($marca).'@example.test','entrar:'.strtolower($marca).'_limit@example.test'] as $bucket){$s=conexion()->prepare('DELETE FROM acceso_limites WHERE clave=?');$s->execute([hash('sha256',$bucket)]);}
    foreach($jars as $jar)@unlink($jar);
}
echo "$total pruebas, $fallos fallos.\n";exit($fallos?1:0);
