<?php
require_once __DIR__.'/comun.php';
function guardar_mensaje(int $id, string $emisor, string $mensaje, ?array $datos=null): void {
    if (!in_array($emisor,['usuario','bag'])) throw new InvalidArgumentException('Emisor no válido.');
    consulta('INSERT INTO mensajes(conversacion_id,emisor,mensaje,datos) VALUES (?,?,?,?)',[$id,$emisor,$mensaje,$datos?json_encode($datos,JSON_UNESCAPED_UNICODE):null]);
    consulta('UPDATE conversaciones SET fecha_actualizacion=NOW() WHERE id=?',[$id]);
}
