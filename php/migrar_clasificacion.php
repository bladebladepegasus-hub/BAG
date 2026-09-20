<?php
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require_once __DIR__.'/conexion.php';
$db=conexion();
if(!$db->query("SHOW COLUMNS FROM usuarios LIKE 'clasificacion_maxima'")->fetch()){
    $db->exec("ALTER TABLE usuarios ADD COLUMN clasificacion_maxima VARCHAR(3) NOT NULL DEFAULT 'A'");
}
echo "Preferencia de clasificación preparada. No se modificaron conversaciones ni edades anteriores.\n";
