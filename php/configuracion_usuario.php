<?php
// País de compra y activación; el idioma de interfaz es una preferencia independiente.
function regiones_bag(): array {
    return ['MX'=>['México','MXN'],'US'=>['Estados Unidos','USD'],'CA'=>['Canadá','CAD'],'AR'=>['Argentina','ARS'],'BR'=>['Brasil','BRL'],'CL'=>['Chile','CLP'],'CO'=>['Colombia','COP'],'PE'=>['Perú','PEN'],'UY'=>['Uruguay','UYU'],'EC'=>['Ecuador','USD'],'CR'=>['Costa Rica','CRC'],'ES'=>['España','EUR'],'GB'=>['Reino Unido','GBP'],'DE'=>['Alemania','EUR'],'FR'=>['Francia','EUR'],'RU'=>['Rusia','RUB']];
}
function validar_configuracion(array $d,array $actual=[]): array {
    $r=[];
    foreach(['region'=>array_keys(regiones_bag()),'idioma'=>['es','en','ru'],'tema'=>['oscuro','claro','sistema']] as $k=>$validos){
        $v=$d[$k]??$actual[$k]??['region'=>'MX','idioma'=>'es','tema'=>'oscuro'][$k];
        if(!is_string($v)||!in_array($v,$validos,true))throw new InvalidArgumentException('Selecciona una opción válida para '.$k.'.');
        $r[$k]=$v;
    }
    return $r;
}
function preparar_configuracion(PDO $db):void {
    $db->exec("CREATE TABLE IF NOT EXISTS configuracion_usuario (usuario_id INT PRIMARY KEY, region CHAR(2) NOT NULL DEFAULT 'MX', idioma VARCHAR(2) NOT NULL DEFAULT 'es', tema VARCHAR(8) NOT NULL DEFAULT 'oscuro', FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB");
}
function guardar_configuracion(int $uid,array $d):void {
    consulta('INSERT INTO configuracion_usuario(usuario_id,region,idioma,tema) VALUES (?,?,?,?) ON DUPLICATE KEY UPDATE region=VALUES(region),idioma=VALUES(idioma),tema=VALUES(tema)',[$uid,$d['region'],$d['idioma'],$d['tema']]);
}
function mercado_actual(): array {
    // Siempre se resuelve en el servidor a partir del perfil, nunca de parámetros de búsqueda.
    $p=perfil();$r=validar_configuracion($p);$pais=regiones_bag()[$r['region']];
    return $r+['nombre'=>$pais[0],'moneda'=>$pais[1],'steam_idioma'=>['es'=>'spanish','en'=>'english','ru'=>'russian'][$r['idioma']],'locale'=>$r['idioma'].'-'.$r['region']];
}
if(PHP_SAPI==='cli' && realpath($_SERVER['SCRIPT_FILENAME'])===__FILE__){require_once __DIR__.'/conexion.php';preparar_configuracion(conexion());echo "Configuración de usuario preparada.\n";}
