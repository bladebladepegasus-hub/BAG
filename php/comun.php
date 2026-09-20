<?php
require_once __DIR__.'/conexion.php';
require_once __DIR__.'/clasificaciones.php';
require_once __DIR__.'/configuracion_usuario.php';
if (session_status() !== PHP_SESSION_ACTIVE) {
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    session_name('BAG_SESSION');
    session_set_cookie_params(['httponly'=>true, 'samesite'=>'Lax', 'secure'=>!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off']);
    session_start();
}
function responder(array $datos, int $estado = 200): void {
    http_response_code($estado);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit;
}
set_exception_handler(function(Throwable $e) {
    responder(['success'=>false,'message'=>$e instanceof InvalidArgumentException ? $e->getMessage() : 'No pude conectar con la base de datos. Inicia MySQL y ejecuta php/crear_base_datos.php.'], $e instanceof InvalidArgumentException ? 422 : 503);
});
function entrada(): array {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') responder(['success'=>false,'message'=>'Usa una petición POST.'],405);
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '') || empty($_SESSION['csrf'])) responder(['success'=>false,'message'=>'Recarga la página para renovar tu sesión.'],403);
    $raw = file_get_contents('php://input', false, null, 0, 12000);
    $datos = json_decode($raw, true);
    if (!is_array($datos)) throw new InvalidArgumentException('Envía datos JSON válidos.');
    return $datos;
}
function texto($valor, int $max = 500): string {
    if (!is_string($valor) || mb_strlen($valor) > $max) throw new InvalidArgumentException('Revisa el texto: supera el límite permitido o no es válido.');
    return trim($valor);
}
function consulta(string $sql, array $params = []): PDOStatement {
    $s = conexion()->prepare($sql); $s->execute($params); return $s;
}
function usuario(): int {
    if(!empty($_SESSION['usuario_id']) && empty($_SESSION['autenticado']) && consulta('SELECT usuario_id FROM cuentas WHERE usuario_id=?',[$_SESSION['usuario_id']])->fetchColumn()){
        unset($_SESSION['usuario_id'],$_SESSION['conversacion_id'],$_SESSION['juego_contexto']);
    }
    if (empty($_SESSION['usuario_id'])) {
        consulta("INSERT INTO usuarios(nombre) VALUES ('Invitado')");
        $_SESSION['usuario_id'] = (int)conexion()->lastInsertId();
    }
    return (int)$_SESSION['usuario_id'];
}
function perfil(): array {
    return consulta("SELECT u.nombre,u.clasificacion_maxima,COALESCE(p.plataforma,'') plataforma,COALESCE(p.genero,'') genero,COALESCE(p.modo_juego,'Ambos') modo_juego,COALESCE(c.region,'MX') region,COALESCE(c.idioma,'es') idioma,COALESCE(c.tema,'oscuro') tema FROM usuarios u LEFT JOIN preferencias p ON p.usuario_id=u.id LEFT JOIN configuracion_usuario c ON c.usuario_id=u.id WHERE u.id=?",[usuario()])->fetch();
}
function nueva_conversacion(): int {
    consulta('INSERT INTO conversaciones(usuario_id) VALUES (?)',[usuario()]);
    $_SESSION['conversacion_id'] = (int)conexion()->lastInsertId();
    unset($_SESSION['juego_contexto']);
    return $_SESSION['conversacion_id'];
}
function conversacion(): int {
    $uid=usuario();$id=$_SESSION['conversacion_id']??null;
    if($id && consulta('SELECT id FROM conversaciones WHERE id=? AND usuario_id=?',[$id,$uid])->fetchColumn())return (int)$id;
    return nueva_conversacion();
}
function normalizar(string $s): string {
    return strtr(mb_strtolower($s), ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n']);
}
function registrar_busqueda(string $q, string $tipo): void {
    consulta('INSERT INTO busquedas(usuario_id,termino,tipo_busqueda) VALUES (?,?,?)',[usuario(),mb_substr($q,0,500),$tipo]);
}
function validar_filtros(array $f): array {
    $opciones=['plataforma'=>['','PC','Xbox','PlayStation','Nintendo Switch','Nintendo Switch 2','Mobile'], 'genero'=>['','Acción','Aventura','RPG','Terror','Carreras','Deportes','Estrategia','Sandbox','Shooter','Supervivencia','Puzzle'], 'modo_juego'=>['Solo','Multijugador','Ambos']];
    foreach($opciones as $k=>$validos) if(isset($f[$k]) && !in_array($f[$k],$validos,true)) throw new InvalidArgumentException('Selecciona un filtro válido para '.$k.'.');
    return $f;
}
