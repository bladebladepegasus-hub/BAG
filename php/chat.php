<?php
require_once __DIR__.'/ia_herramientas.php';
require_once __DIR__.'/ia_openai.php';
require_once __DIR__.'/filtro_seguridad.php';
require_once __DIR__.'/guardar_mensaje.php';

$d=entrada();
$mensaje=texto($d['message'] ?? '',1500);
if ($mensaje==='') throw new InvalidArgumentException('Escribe un mensaje para comenzar.');

try {
    verificar_configuracion_ia();
    set_time_limit(210);
    $p=perfil();


    $id=conversacion();
    $historial=contexto_conversacion_ia($id);
    if (filtro_seguridad($mensaje)) {
        $r=['success'=>true,'intent'=>'SEGURIDAD','engine'=>'seguridad','message'=>'No puedo ayudar con hacks, robo de cuentas o trampas competitivas. Sí puedo ayudarte a conseguir recursos y avanzar de forma legítima. ¿En qué juego estás?','games'=>[]];
    } else {
        $r=responder_con_ia($mensaje,$p,$historial,fn($nombre,$argumentos)=>ejecutar_herramienta_ia($nombre,$argumentos,$p));
    }
    $db=conexion();
    $db->beginTransaction();
    try {
        if (!empty($r['new_chat'])) $id=nueva_conversacion();

        $vacia=consulta('SELECT COUNT(*) FROM mensajes WHERE conversacion_id=?',[$id])->fetchColumn()==0;
        if ($vacia) consulta('UPDATE conversaciones SET titulo=? WHERE id=?',[mb_substr($mensaje,0,100),$id]);
        $r['conversation_id']=$id;
        $r['time']=date(DATE_ATOM);
        guardar_mensaje($id,'usuario',$mensaje);
        guardar_mensaje($id,'bag',$r['message'],$r);
        $db->commit();
    } catch (Throwable $e) {
        if ($db->inTransaction()) $db->rollBack();
        throw $e;
    }
    responder($r);
} catch (ErrorIA $e) {
    responder(['success'=>false,'engine'=>'openai','code'=>$e->codigo,'message'=>$e->getMessage()],$e->estado);
}
