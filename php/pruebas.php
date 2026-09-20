<?php
// Ejecutar solo desde consola: php php/pruebas.php
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require_once __DIR__.'/conexion.php';
$base=$argv[1] ?? 'http://localhost/BAG/php/';
$cookie=tempnam(sys_get_temp_dir(),'bag_test_');
$cookie2=tempnam(sys_get_temp_dir(),'bag_other_');
$csrf=''; $fallos=0; $total=0; $uid=null; $uid2=null;
function peticion(string $ruta, ?array $datos=null, ?string $jar=null, ?string $token=null): array {
    global $base,$cookie,$csrf;
    $c=curl_init($base.$ruta);
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$jar??$cookie,CURLOPT_COOKIEJAR=>$jar??$cookie,CURLOPT_TIMEOUT=>30]);
    if($datos!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($datos),CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-CSRF-Token: '.($token??$csrf)]]);
    $raw=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);
    return ['status'=>$status,'data'=>json_decode($raw,true),'raw'=>$raw];
}
function verificar(bool $cond,string $nombre): void { global $fallos,$total; $total++; if(!$cond)$fallos++; echo ($cond?'OK  ':'FAIL').' '.$nombre.PHP_EOL; }
function chat_test(string $m): array { return peticion('chat.php',['message'=>$m])['data']??[]; }
try {
    $s=peticion('preferencias.php');$csrf=$s['data']['csrf']??'';
    verificar($s['status']===200&&strlen($csrf)===64,'Sesión y CSRF');
    if(!$csrf)throw new RuntimeException('Sesión no disponible');
    $marca='BAG_TEST_'.bin2hex(random_bytes(5));
    $d=peticion('guardar_preferencias.php',['nombre'=>$marca,'clasificacion_maxima'=>'B','region'=>'MX','idioma'=>'es','tema'=>'oscuro','plataforma'=>'PC','genero'=>'Aventura','modo_juego'=>'Solo']);
    $st=conexion()->prepare('SELECT id FROM usuarios WHERE nombre=?');$st->execute([$marca]);$uid=$st->fetchColumn();
    verificar($d['status']===200&&(bool)$uid,'Guardar preferencias');
    verificar(peticion('preferencias.php')['data']['profile']['modo_juego']==='Solo','Recuperar preferencias');
    verificar(!array_key_exists('key',$s['data']['ai']),'Estado IA no contiene clave');
    $r=peticion('nueva_conversacion.php',[]);$viejo=$r['data']['conversation_id'];
    $insertar=conexion()->prepare('INSERT INTO mensajes(conversacion_id,emisor,mensaje,datos) VALUES (?,?,?,?)');
    $insertar->execute([$viejo,'usuario','Mensaje de prueba de historial',null]);
    $insertar->execute([$viejo,'bag','Respuesta de prueba',json_encode(['engine'=>'fixture','sources'=>[['url'=>'https://www.minecraft.net/','title'=>'Fuente de prueba']]])]);
    $antes=peticion('historial.php')['data']['conversations'];peticion('nueva_conversacion.php',[]);$despues=peticion('historial.php')['data']['conversations'];
    verificar(count($despues)===count($antes)+1,'Nuevo chat conserva historial');
    $r=peticion('historial.php?id='.$viejo);verificar(count($r['data']['messages'])===2,'Recupera mensajes');
    verificar($r['data']['messages'][1]['datos']['sources'][0]['title']==='Fuente de prueba','Conserva fuentes');
    verificar(peticion('chat.php',['message'=>''])['status']===422,'Mensaje vacío rechazado');
    verificar(peticion('chat.php',['message'=>str_repeat('a',1501)])['status']===422,'Mensaje demasiado largo rechazado');
    verificar(peticion('chat.php',['message'=>'Hola'],null,'incorrecto')['status']===403,'CSRF incorrecto rechazado');
    verificar(peticion('guardar_preferencias.php',['clasificacion_maxima'=>'99'])['status']===422,'Clasificación inválida');
    verificar(peticion('guardar_preferencias.php',['plataforma'=>'<script>'])['status']===422,'Plataforma inválida');
    verificar(peticion('recomendaciones.php?modo_juego=invalido')['status']===422,'Modo inválido');
    verificar(peticion('buscar_juego.php?genero=invalido')['status']===422,'Género inválido');
    $otro=peticion('preferencias.php',null,$cookie2);$token2=$otro['data']['csrf'];
    $marca2=$marca.'_other';peticion('guardar_preferencias.php',['nombre'=>$marca2,'modo_juego'=>'Ambos'],$cookie2,$token2);$st->execute([$marca2]);$uid2=$st->fetchColumn();
    verificar(peticion('historial.php?id='.$viejo,null,$cookie2)['status']===404,'Historial privado entre usuarios');
    peticion('historial.php',['id'=>(int)$viejo],$cookie2,$token2);verificar(peticion('historial.php?id='.$viejo)['status']===200,'Otro usuario no elimina conversaciones');
    peticion('historial.php',['id'=>(int)$viejo]);verificar(peticion('historial.php?id='.$viejo)['status']===404,'Elimina conversación propia');
    verificar(peticion('config.php')['status']===403,'Configuración protegida');
    verificar(peticion('cuenta.php')['status']===405,'Cuenta requiere POST');
    verificar(peticion('guardar_preferencias.php')['status']===405,'Guardar requiere POST');
} catch(Throwable $e){verificar(false,'Excepción de prueba: '.$e->getMessage());}
finally{foreach([$uid,$uid2]as $id)if($id){$st=conexion()->prepare('DELETE FROM usuarios WHERE id=?');$st->execute([$id]);}@unlink($cookie);@unlink($cookie2);}
echo "$total pruebas, $fallos fallos. Sin peticiones a OpenAI.\n";exit($fallos?1:0);
