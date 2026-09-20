<?php
require_once __DIR__.'/config.php';
function conexion(bool $seleccionar = true): PDO {
    static $db;
    if ($seleccionar && $db) return $db;
    $pdo = new PDO('mysql:host='.DB_HOST.';port='.DB_PORT.($seleccionar ? ';dbname='.DB_NAME : '').';charset=utf8mb4', DB_USER, DB_PASSWORD, [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES=>false]);
    if ($seleccionar) $db = $pdo;
    return $pdo;
}
