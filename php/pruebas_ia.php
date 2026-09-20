<?php
// Pruebas del protocolo y de herramientas. No llaman a OpenAI ni gastan saldo.
if (PHP_SAPI!=='cli') { http_response_code(404); exit; }
require_once __DIR__.'/ia_herramientas.php';
require_once __DIR__.'/ia_openai.php';
require_once __DIR__.'/guardar_mensaje.php';

$total=0; $fallos=0; $uid=null;
function comprobar_ia(bool $condicion,string $nombre): void {
    global $total,$fallos;
    $total++; if(!$condicion)$fallos++;
    echo ($condicion?'OK  ':'FAIL').' '.$nombre.PHP_EOL;
}
function respuesta_fixture(string $texto='Respuesta de prueba.',array $citas=[]): array {
    return ['status'=>'completed','output'=>[['type'=>'message','role'=>'assistant','content'=>[['type'=>'output_text','text'=>$texto,'annotations'=>$citas]]]]];
}
function llamada_fixture(string $nombre,array|string $args,string $id='call_test'): array {
    return ['type'=>'function_call','name'=>$nombre,'call_id'=>$id,'arguments'=>is_array($args)?json_encode($args):$args];
}
function esperar_error_ia(callable $fn,string $codigo,string $nombre): void {
    try { $fn(); comprobar_ia(false,$nombre); }
    catch(ErrorIA $e){comprobar_ia($e->codigo===$codigo,$nombre);}
}

try {
    $uid=usuario();
    $p=['clasificacion_maxima'=>'A','region'=>'MX','idioma'=>'es','plataforma'=>'','genero'=>'','modo_juego'=>'Ambos'];
    $args=['operacion'=>'buscar','consulta'=>'Minecraft','plataforma'=>'','genero'=>'','modo_juego'=>'','similar_a'=>''];
    $herramientas=herramientas_ia(true);
    comprobar_ia(in_array('web_search',array_column($herramientas,'type')),'Herramienta de búsqueda web habilitada');
    comprobar_ia(!in_array('web_search',array_column(herramientas_ia(false),'type')),'Búsqueda web configurable');
    foreach($herramientas as $h) if($h['type']==='function') comprobar_ia($h['strict'] && $h['parameters']['additionalProperties']===false && count($h['parameters']['properties'])===count($h['parameters']['required']),'Esquema estricto: '.$h['name']);
    // El protocolo usa fixtures: los catálogos se comprueban en pruebas_revision.php --live.
    $resultado=['games'=>[['id'=>'steam-fixture','nombre'=>'Minecraft','descripcion'=>'Ficha de prueba de protocolo']]];
    $ejecutarFixture=fn($n,$a)=>$resultado;
    comprobar_ia(str_contains(instrucciones_ia(['idioma'=>'en','region'=>'US']),'Responde en inglés'),'Idioma del perfil en instrucciones');
    comprobar_ia(str_contains(instrucciones_ia(['idioma'=>'ru','region'=>'RU']),'"region":"RU"'),'Región guardada en contexto');
    comprobar_ia(in_array('Nintendo Switch 2',$herramientas[0]['parameters']['properties']['plataforma']['enum']),'Switch 2 admitida por herramientas');
    try { ejecutar_herramienta_ia('exec',['command'=>'test'],$p); comprobar_ia(false,'Herramienta desconocida bloqueada'); }
    catch(InvalidArgumentException $e){comprobar_ia(true,'Herramienta desconocida bloqueada');}
    $id=nueva_conversacion();
    guardar_mensaje($id,'usuario','Me interesa Minecraft.');
    guardar_mensaje($id,'bag','Encontré estos juegos.',['games'=>$resultado['games']]);
    $historial=contexto_conversacion_ia($id);
    comprobar_ia(count($historial)===2 && $historial[0]['role']==='user' && str_contains($historial[1]['content'],'Minecraft'),'Contexto conserva roles y nombres de tarjetas');
    $otro=nueva_conversacion();
    comprobar_ia(contexto_conversacion_ia($otro)===[],'Nuevo chat no hereda contexto anterior');
    comprobar_ia(count(contexto_conversacion_ia($id))===2,'Nuevo chat conserva historial anterior');
    consulta('DELETE FROM conversaciones WHERE id=?',[$otro]);
    comprobar_ia(conversacion()!==$otro,'Chat recupera una conversación eliminada');
    $contador=0; $peticiones=[];
    $transporte=function($req) use (&$contador,&$peticiones,$args) {
        $peticiones[]=$req;
        if($contador++===0) return ['status'=>'completed','output'=>[
            ['type'=>'reasoning','id'=>'rs_test','summary'=>[],'encrypted_content'=>'cifrado_de_prueba'],
            llamada_fixture('consultar_catalogo',$args)
        ]];
        return respuesta_fixture('Encontré Minecraft en la consulta de prueba.');
    };
    $r=responder_con_ia('¿En qué plataformas está ese juego?',$p,$historial,$ejecutarFixture,$transporte);
    comprobar_ia($contador===2 && $r['engine']==='openai' && $r['games'][0]['nombre']==='Minecraft','Ciclo modelo → herramienta → respuesta');
    comprobar_ia($peticiones[0]['input'][2]['content']==='¿En qué plataformas está ese juego?','Pregunta actual sigue al historial');
    comprobar_ia($peticiones[0]['store']===false,'Persistencia remota de Responses desactivada');
    $funciones=array_values(array_filter($peticiones[1]['input'],fn($x)=>($x['type']??'')==='function_call_output'));
    comprobar_ia($funciones[0]['call_id']==='call_test' && str_contains($funciones[0]['output'],'Minecraft'),'Salida de herramienta asociada al call_id correcto');
    comprobar_ia(count(array_filter($peticiones[1]['input'],fn($x)=>($x['type']??'')==='reasoning'))===1,'Continuación conserva razonamiento cifrado');
    $texto='Minecraft tiene varias plataformas. [fuente]';
    $cita=['type'=>'url_citation','start_index'=>35,'end_index'=>43,'url'=>'https://www.minecraft.net/','title'=>'Minecraft oficial'];
    $web=respuesta_fixture($texto,[$cita]);
    array_unshift($web['output'],['type'=>'web_search_call','status'=>'completed']);
    $r=responder_con_ia('Busca información actual',$p,[],fn()=>[],fn()=>$web);
    comprobar_ia($r['web_searched'] && count($r['sources'])===1 && count($r['citations'])===1,'Respuesta web conserva fuentes y citas');
    $insegura=extraer_salida_ia(respuesta_fixture('test',[array_replace($cita,['url'=>'javascript:alert(1)'])])['output']);
    comprobar_ia($insegura['sources']===[] && $insegura['citations']===[],'Citas con protocolo ejecutable rechazadas');
    $desfase=extraer_salida_ia(respuesta_fixture('test',[array_replace($cita,['start_index'=>999,'end_index'=>1000])])['output']);
    comprobar_ia(count($desfase['sources'])===1 && $desfase['citations']===[],'Cita fuera de rango mantiene fuente sin romper texto');
    $reintentos=0;
    $r=responder_con_ia('Hola',$p,[],fn($n,$a)=>ejecutar_herramienta_ia($n,$a,$p),function($req)use(&$reintentos){
        if($reintentos++===0)return ['status'=>'completed','output'=>[llamada_fixture('consultar_catalogo','{mal json')]];
        $ultima=end($req['input']);
        comprobar_ia(str_contains($ultima['output'],'error'),'JSON inválido se devuelve al modelo como error de herramienta');
        return respuesta_fixture();
    });
    comprobar_ia($r['message']==='Respuesta de prueba.','Modelo puede recuperarse de argumentos inválidos');
    foreach([401=>'IA_CLAVE_INVALIDA',429=>'IA_LIMITE',400=>'IA_CONFIGURACION',403=>'IA_CONFIGURACION',404=>'IA_CONFIGURACION',500=>'IA_PROVEEDOR'] as $http=>$codigo) esperar_error_ia(fn()=>interpretar_http_ia($http,'dato-secreto-no-visible'),$codigo,'Error HTTP '.$http.' se normaliza');
    esperar_error_ia(fn()=>interpretar_http_ia(200,'no es json'),'IA_RESPUESTA_INVALIDA','Respuesta malformada rechazada');
    esperar_error_ia(fn()=>interpretar_http_ia(200,'{"status":"incomplete","output":[]}'),'IA_RESPUESTA_INCOMPLETA','Respuesta incompleta rechazada');
    esperar_error_ia(fn()=>responder_con_ia('Hola',$p,[],fn()=>[],fn()=>['status'=>'completed','output'=>[]]),'IA_SIN_RESPUESTA','Respuesta vacía no se disfraza como éxito');
    $ciclos=0;
    esperar_error_ia(function()use($p,&$ciclos){return responder_con_ia('Busca',$p,[],fn()=>[],function($req)use(&$ciclos){$ciclos++;return ['status'=>'completed','output'=>[llamada_fixture('consultar_catalogo',[])]];});},'IA_LIMITE_HERRAMIENTAS','Ciclo interminable limitado');
    comprobar_ia($ciclos===4,'Máximo de cuatro solicitudes al modelo');
    $anterior=$OPENAI_API_KEY;$OPENAI_API_KEY='';
    esperar_error_ia(fn()=>verificar_configuracion_ia(),'IA_SIN_CLAVE','Sin clave no hay respuesta de IA falsa');
    comprobar_ia(estado_ia()['configured']===false && !array_key_exists('key',estado_ia()),'Estado público no expone la clave');
    $OPENAI_API_KEY=$anterior;
} catch(Throwable $e) {
    comprobar_ia(false,'Excepción de prueba: '.$e->getMessage());
} finally {
    if($uid) consulta('DELETE FROM usuarios WHERE id=?',[$uid]);
}
echo "\n$total pruebas de protocolo/herramientas, $fallos fallos. No equivalen a una conexión real con OpenAI.\n";
exit($fallos?1:0);
