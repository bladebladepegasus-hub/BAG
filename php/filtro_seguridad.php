<?php
function filtro_seguridad(string $mensaje): bool {
    return (bool)preg_match('/\b(hacks?|cheats?|aimbot|wallhack|malware|keylogger)\b|monedas infinitas|robar.*(cuenta|contrasena)|generador.*monedas|explotar.*servidor/i', normalizar($mensaje));
}
