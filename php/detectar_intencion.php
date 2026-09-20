<?php
require_once __DIR__.'/comun.php';
function detectar_intencion(string $mensaje): string {
    $n=normalizar($mensaje);
    $reglas=[
        'NUEVO_CHAT'=>'nuevo chat|nueva conversacion|otra conversacion|reinicia',
        'HISTORIAL'=>'historial|conversaciones anteriores',
        'PREFERENCIAS'=>'preferencias|mis gustos|mi perfil',
        'COMPARAR_PRECIO'=>'precio|cuesta|cuestan|barato|cuanto vale|costo',
        'BUSCAR_SIMILARES'=>'parecido|similar',
        'CONSEJO_JUEGO'=>'jefe|mision|arma|personaje|mecanica|derrot|paso esta|atorado|mejorar|consejo|ayuda.*partida',
        'RECOMENDAR_POR_EDAD'=>'\d+\s*anos',
        'RECOMENDAR_POR_GENERO'=>'terror|carreras|rpg|sandbox|shooter|puzzle|estrategia|supervivencia|aventura|accion|deportes',
        'RECOMENDAR_POR_PLATAFORMA'=>'(quiero|recomienda|juegos? para).*(xbox|pc|playstation|switch|mobile)',
        'RECOMENDAR_JUEGO'=>'recomiend|me gustan|dinosaurios',
        'DETALLE_JUEGO'=>'plataformas|informacion|detalles|de que trata|disponible',
        'BUSCAR_JUEGO'=>'busca|encuentra',
        'SALUDO'=>'^(hola|buenas|buenos dias|hey)[ !.🎮]*$',
        'DESPEDIDA'=>'adios|hasta luego|gracias',
        'AYUDA'=>'ayuda|que puedes hacer'
    ];
    foreach($reglas as $i=>$patron) if(preg_match('/'.$patron.'/u',$n)) return $i;
    return 'NO_ENTENDIDO';
}
