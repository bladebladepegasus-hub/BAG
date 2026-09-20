<?php
function consejos(string $mensaje): string {
    $n=normalizar($mensaje);
    if (str_contains($n,'arma')) return 'Elige un arma que encaje con tu alcance y recursos. ¿En qué juego estás, qué armas tienes y contra quién vas a luchar?';
    if (str_contains($n,'personaje')) return 'Depende del equipo y de tu estilo: apoyo para ayudar, defensa para resistir o movilidad para explorar. ¿Qué juego y personajes tienes disponibles?';
    if (str_contains($n,'mecanica')) return 'Dime el juego y la mecánica que te cuesta entender; con ese contexto puedo orientar mejor la explicación.';
    if (str_contains($n,'jefe')) return 'Observa sus patrones, reserva recursos para curarte y ataca al terminar sus animaciones. ¿Cómo se llama el jefe y de qué juego es?';
    return 'Revisa el objetivo y el equipo disponible, explora rutas alternativas y guarda recursos. ¿En qué juego y misión estás? Mis consejos son generales; no inventaré una solución específica sin contexto.';
}
