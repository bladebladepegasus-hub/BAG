<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/configuracion_usuario.php';
require_once __DIR__.'/tiendas_extra.php';
$total=0;$fallos=0;$jar=tempnam(sys_get_temp_dir(),'bag_rev_');$csrf='';$uid=null;$marca='REV_'.bin2hex(random_bytes(5));
function revision(bool $ok,string $texto):void{global $total,$fallos;$total++;if(!$ok)$fallos++;echo ($ok?'OK ':'FAIL ').$texto.PHP_EOL;}
function http_revision(string $ruta,?array $datos=null):array{global $jar,$csrf;$c=curl_init('http://localhost/BAG/'.$ruta);curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_COOKIEFILE=>$jar,CURLOPT_COOKIEJAR=>$jar,CURLOPT_TIMEOUT=>80]);if($datos!==null)curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($datos),CURLOPT_HTTPHEADER=>['Content-Type: application/json','X-CSRF-Token: '.$csrf]]);$raw=curl_exec($c);$status=curl_getinfo($c,CURLINFO_HTTP_CODE);curl_close($c);$d=json_decode($raw,true)??[];if(isset($d['csrf']))$csrf=$d['csrf'];return ['status'=>$status,'data'=>$d];}
try{
 foreach(['inicio','chat','buscar','precios','recomendaciones','historial','preferencias','ayuda','cuenta']as $v)revision(http_revision('vistas/vista_'.$v.'.html')['status']===200,'Página '.$v);
 $s=http_revision('php/preferencias.php');revision($s['status']===200&&!empty($s['data']['csrf']),'Sesión y CSRF');
 if($s['status']!==200||empty($s['data']['profile']))throw new RuntimeException('La sesión no está disponible; se detiene la prueba sin continuar con datos vacíos.');
 $p=$s['data']['profile'];$p['nombre']=$marca;
 foreach([['MX','es','oscuro'],['RU','ru','claro'],['ES','en','sistema']] as [$r,$l,$t]){$p=array_replace($p,['region'=>$r,'idioma'=>$l,'tema'=>$t]);$save=http_revision('php/guardar_preferencias.php',$p);$read=http_revision('php/preferencias.php')['data']['profile'];revision($save['status']===200&&$read['region']===$r&&$read['idioma']===$l&&$read['tema']===$t,'Persistencia '.$r.'/'.$l.'/'.$t);}
 $stmt=conexion()->prepare('SELECT id FROM usuarios WHERE nombre=?');$stmt->execute([$marca]);$uid=(int)$stmt->fetchColumn();
 foreach(['region'=>'ZZ','idioma'=>'xx','tema'=>'rojo','clasificacion_maxima'=>'99']as $k=>$v)revision(http_revision('php/guardar_preferencias.php',array_replace($p,[$k=>$v]))['status']===422,'Rechazo '.$k.' inválido');
 $d=['countryIds'=>[140,10],'regionBlacklist'=>[],'productRegions'=>['latam']];revision(eneba_disponible($d,'MX'),'LATAM admite MX cuando está en la lista');revision(!eneba_disponible($d,'RU'),'LATAM no se atribuye a Rusia');$d['regionBlacklist']=['mexico'];revision(!eneba_disponible($d,'MX'),'Exclusión regional prevalece');revision(!eneba_disponible(['productRegions'=>['global']],'MX'),'Global sin países no garantiza activación');
 foreach(['php/config_local.php','php/comun.php','php/base_datos.sql','php/configuracion_usuario.php']as $ruta)revision(http_revision($ruta)['status']===403,'Archivo interno protegido: '.$ruta);
 revision(http_revision('php/buscar_juego.php?q=x&tienda=Desconocida')['status']===422,'Tienda inválida');
 revision(http_revision('php/comparar_precios.php?q=')['status']===422,'Precio requiere título');
 revision(http_revision('php/detalle_juego.php?id=1')['status']===404,'Detalle no recupera catálogo demo');
 if(in_array('--ia',$argv,true)){
   $r=http_revision('php/chat.php',['message'=>'Responde únicamente: conexión verificada.']);
   echo 'Conexión real IA: '.json_encode(['http'=>$r['status'],'success'=>$r['data']['success']??false,'code'=>$r['data']['code']??null,'message'=>$r['data']['message']??'Sin respuesta'],JSON_UNESCAPED_UNICODE).PHP_EOL;
   revision($r['status']===200||isset($r['data']['code']),'Chat devuelve respuesta o error identificable');
 }
 $p['region']='MX';$p['idioma']='es';$p['tema']='oscuro';http_revision('php/guardar_preferencias.php',$p);
 if(in_array('--live',$argv,true)){
   foreach(['Steam'=>'monster hunter','GOG'=>'witcher','Xbox'=>'halo','Eneba'=>'monster hunter']as $tienda=>$q){$r=http_revision('php/buscar_juego.php?'.http_build_query(['q'=>$q,'tienda'=>$tienda]));$games=$r['data']['games']??[];revision($r['status']===200&&count($games)>0,'Búsqueda real '.$tienda);revision(count(array_filter($games,fn($j)=>empty($j['region_verificada'])||$j['fuente']!==$tienda))===0,'Filtro regional y fuente '.$tienda);if($games){$j=$games[0];revision(!empty($j['imagen'])&&str_starts_with($j['url'],'https://'),'Portada y enlace '.$tienda);$detalle=http_revision('php/detalle_juego.php?'.http_build_query(['id'=>$j['id'],'q'=>$j['nombre']]));revision(($detalle['data']['game']['id']??null)===$j['id'],'Detalle '.$tienda);}}
   $r=http_revision('php/comparar_precios.php?q=halo&tienda=Xbox');revision(!empty($r['data']['prices'][0]['filas']),'Tabla de precios real');
   $r=http_revision('php/buscar_juego.php?q=mario&tienda=Nintendo');revision($r['status']===200&&$r['data']['games']===[],'Nintendo España no aparece para México');
   $p['region']='ES';http_revision('php/guardar_preferencias.php',$p);$r=http_revision('php/buscar_juego.php?q=mario&tienda=Nintendo');revision(!empty($r['data']['games']),'Nintendo disponible al consultar España');
   $r=http_revision('php/recomendaciones.php?clasificacion_maxima=B&plataforma=Nintendo%20Switch');$games=$r['data']['games']??[];revision($r['status']===200&&count($games)>0,'Recomendaciones en vivo');revision(count(array_filter($games,fn($j)=>!in_array($j['categoria_filtro'],['A','B'])||empty($j['motivo'])))===0,'Clasificación y motivos en recomendaciones');
 }
}catch(Throwable $e){revision(false,'Excepción: '.$e->getMessage());}finally{if($uid){$s=conexion()->prepare('DELETE FROM usuarios WHERE id=? AND nombre=?');$s->execute([$uid,$marca]);}@unlink($jar);}
echo "$total comprobaciones, $fallos fallos.\n";exit($fallos?1:0);
