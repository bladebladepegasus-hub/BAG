<?php
require_once __DIR__.'/comun.php';
require_once __DIR__.'/filtro_edad.php';
$d=entrada(); $nombre=texto($d['nombre'] ?? '',80) ?: 'Invitado'; $clasificacion=clasificacion_valida($d['clasificacion_maxima']??'A');
$plataforma=texto($d['plataforma'] ?? '',40); $genero=texto($d['genero'] ?? '',40); $modo=texto($d['modo_juego'] ?? 'Ambos',20);
validar_filtros(['plataforma'=>$plataforma,'genero'=>$genero,'modo_juego'=>$modo]);
$config=validar_configuracion($d,perfil());
$uid=usuario(); $db=conexion(); $db->beginTransaction();
guardar_configuracion($uid,$config);
consulta('UPDATE usuarios SET nombre=?,clasificacion_maxima=? WHERE id=?',[$nombre,$clasificacion,$uid]);
consulta('INSERT INTO preferencias(usuario_id,plataforma,genero,modo_juego) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE plataforma=VALUES(plataforma),genero=VALUES(genero),modo_juego=VALUES(modo_juego)',[$uid,$plataforma,$genero,$modo]);
$db->commit(); responder(['success'=>true,'message'=>'Preferencias guardadas. Las usaremos en tus recomendaciones.']);
