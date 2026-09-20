<?php
require_once __DIR__.'/comun.php';
$uid=usuario();
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $d=entrada(); $id=filter_var($d['id'] ?? null,FILTER_VALIDATE_INT);
    if (!$id) throw new InvalidArgumentException('Conversación no válida.');
    consulta('DELETE FROM conversaciones WHERE id=? AND usuario_id=?',[$id,$uid]);
    if (($_SESSION['conversacion_id'] ?? null)===$id) unset($_SESSION['conversacion_id'],$_SESSION['juego_contexto']);
    responder(['success'=>true,'message'=>'Conversación eliminada.']);
}
if (isset($_GET['id'])) {
    $id=filter_var($_GET['id'],FILTER_VALIDATE_INT);
    $c=consulta('SELECT * FROM conversaciones WHERE id=? AND usuario_id=?',[$id?:0,$uid])->fetch();
    if (!$c) responder(['success'=>false,'message'=>'Conversación no encontrada.'],404);
    $mensajes=consulta('SELECT emisor,mensaje,datos,fecha FROM mensajes WHERE conversacion_id=? ORDER BY id',[$id])->fetchAll();
    foreach($mensajes as &$m) $m['datos']=$m['datos']?json_decode($m['datos'],true):null;
    responder(['success'=>true,'conversation'=>$c,'messages'=>$mensajes]);
}
$lista=consulta('SELECT c.*, COUNT(m.id) cantidad_mensajes, (SELECT mensaje FROM mensajes WHERE conversacion_id=c.id AND emisor=\'usuario\' ORDER BY id LIMIT 1) primer_mensaje FROM conversaciones c LEFT JOIN mensajes m ON m.conversacion_id=c.id WHERE c.usuario_id=? GROUP BY c.id ORDER BY c.fecha_actualizacion DESC,c.id DESC',[$uid])->fetchAll();
responder(['success'=>true,'conversations'=>$lista]);
