<?php
function preparar_cuentas(PDO $db):void {
    $db->exec("CREATE TABLE IF NOT EXISTS cuentas (usuario_id INT PRIMARY KEY, correo VARCHAR(254) CHARACTER SET ascii COLLATE ascii_general_ci NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, creada DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP, FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB");
    $db->exec("CREATE TABLE IF NOT EXISTS acceso_limites (clave CHAR(64) PRIMARY KEY, intentos INT NOT NULL DEFAULT 0, inicio DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB");
}
if(PHP_SAPI==='cli' && realpath($_SERVER['SCRIPT_FILENAME'])===__FILE__){require_once __DIR__.'/conexion.php';preparar_cuentas(conexion());echo "Tablas de cuentas preparadas.\n";}
