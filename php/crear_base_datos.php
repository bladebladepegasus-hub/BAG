<?php
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/cuentas_esquema.php';
require_once __DIR__.'/configuracion_usuario.php';
header('Content-Type: text/plain; charset=utf-8');
try {
    $db = conexion(false);
    $existe = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='bag' AND table_name IN ('usuarios','preferencias','conversaciones','mensajes','busquedas')")->fetchColumn() == 5;
    $db->exec(file_get_contents(__DIR__.'/base_datos.sql'));
    preparar_cuentas($db);
    preparar_configuracion($db);
    if (!$db->query("SHOW COLUMNS FROM usuarios LIKE 'clasificacion_maxima'")->fetch()) $db->exec("ALTER TABLE usuarios ADD COLUMN clasificacion_maxima VARCHAR(3) NOT NULL DEFAULT 'A'");
    if (!$db->query('SELECT COUNT(*) FROM usuarios')->fetchColumn()) $db->exec("INSERT INTO usuarios(nombre) VALUES ('Invitado')");
    echo $existe ? 'No fue necesario volver a crear la base de datos.' : 'Base de datos B.A.G creada correctamente.';
} catch (Throwable $e) {
    http_response_code(503);
    echo 'No fue posible preparar MySQL. Inicia MySQL en XAMPP y revisa php/config.php.';
}
