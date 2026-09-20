<?php
require_once __DIR__.'/recomendador_online.php';

function herramientas_ia(bool $web=true): array {
    $texto=['type'=>'string'];
    $herramientas=[[
        'type'=>'function','name'=>'consultar_catalogo','description'=>'Busca tarjetas de juegos; usa recomendar_juegos para recomendaciones con filtro de clasificación obligatorio. Consulta: título o palabras clave.','strict'=>true,
        'parameters'=>[
            'type'=>'object','additionalProperties'=>false,
            'properties'=>[
                'operacion'=>['type'=>'string','enum'=>['buscar','recomendar_juegos','detallar']],
                'consulta'=>$texto,
                'plataforma'=>['type'=>'string','enum'=>['','PC','Xbox','PlayStation','Nintendo Switch','Nintendo Switch 2','Mobile']],
                'genero'=>['type'=>'string','enum'=>['','Acción','Aventura','RPG','Terror','Carreras','Deportes','Estrategia','Sandbox','Shooter','Supervivencia','Puzzle']],
                'modo_juego'=>['type'=>'string','enum'=>['','Solo','Multijugador','Ambos']],
                'similar_a'=>$texto
            ],
            'required'=>['operacion','consulta','plataforma','genero','modo_juego','similar_a']
        ]
    ],[
        'type'=>'function','name'=>'consultar_precios','description'=>'Consulta precios en las tiendas conectadas para la región guardada; devuelve enlaces, moneda y condiciones.','strict'=>true,
        'parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['juego'=>$texto],'required'=>['juego']]
    ],[
        'type'=>'function','name'=>'abrir_seccion','description'=>'Enlace al historial o preferencias, o nuevo chat conservando los anteriores. No elimina datos.','strict'=>true,
        'parameters'=>['type'=>'object','additionalProperties'=>false,'properties'=>['seccion'=>['type'=>'string','enum'=>['historial','preferencias','nuevo_chat']]],'required'=>['seccion']]
    ]];
    if ($web) $herramientas[]=['type'=>'web_search'];
    return $herramientas;
}

function ejecutar_herramienta_ia(string $nombre, array $d, array $perfil): array {
    if ($nombre==='consultar_catalogo') {
        $op=texto($d['operacion'] ?? '',30);
        $q=texto($d['consulta'] ?? '',200);
        $similar=texto($d['similar_a'] ?? '',120);
        $f=$perfil;
        foreach (['plataforma','genero','modo_juego'] as $k) {
            $v=texto($d[$k] ?? '',40);
            if ($v!=='') $f[$k]=$v;
        }
        validar_filtros($f);
        if (!in_array($op,['buscar','recomendar_juegos','detallar'],true)) throw new InvalidArgumentException('Operación no admitida.');
        // La IA no puede cambiar la clasificación guardada mediante argumentos de herramientas.
        $maxima=clasificacion_valida($perfil['clasificacion_maxima']??'A');
        if ($op==='recomendar_juegos') {
            $resultado=recomendar_online($f,$similar);$juegos=$resultado['games'];
        } else {
            $resultado=buscar_online($q,['plataforma'=>$d['plataforma'] ?? '', 'genero'=>$d['genero'] ?? '']);$juegos=$resultado['games'];
            $juegos=advertir_clasificacion(array_slice($juegos,0,6),$maxima);
        }
        foreach($juegos as &$j) $j['descripcion']=mb_substr($j['descripcion'],0,1800);
        registrar_busqueda($q ?: $similar,'ia_'.$op);
        return ['games'=>$juegos,'clasificacion_aplicada'=>$maxima,'region_aplicada'=>mercado_actual()['region'],'nota'=>$juegos?'Fichas consultadas para la región guardada. Conserva la moneda y condiciones originales.':'No hay coincidencias con disponibilidad regional comprobada.','avisos'=>$resultado['warnings']??$resultado['message']??''];
    }
    if ($nombre==='consultar_precios') {
        $q=texto($d['juego'] ?? '',200);
        if ($q==='') throw new InvalidArgumentException('Falta el videojuego.');
        registrar_busqueda($q,'ia_precios');
        $r=buscar_online($q);return ['prices'=>agrupar_precios_online(array_slice($r['games'],0,12)),'region_aplicada'=>mercado_actual()['region'],'nota'=>'Precios consultados para el país guardado. No sustituyas ofertas ausentes por ofertas de otra región.','avisos'=>$r['warnings']];
    }
    if ($nombre==='abrir_seccion') {
        $seccion=texto($d['seccion'] ?? '',30);
        if ($seccion==='nuevo_chat') return ['new_chat'=>true,'nota'=>'La app abrirá la nueva conversación al completar esta respuesta; el historial anterior permanece guardado.'];
        if (!in_array($seccion,['historial','preferencias'],true)) throw new InvalidArgumentException('Sección no válida.');
        $r=['action'=>['label'=>$seccion==='historial'?'Ver historial':'Mis preferencias','url'=>'vista_'.$seccion.'.html']];
        if ($seccion==='historial') $r['cantidad_conversaciones']=(int)consulta('SELECT COUNT(*) FROM conversaciones WHERE usuario_id=?',[usuario()])->fetchColumn();
        return $r;
    }
    throw new InvalidArgumentException('Herramienta no permitida.');
}

function contexto_conversacion_ia(int $id): array {
    $filas=consulta('SELECT m.emisor,m.mensaje,m.datos FROM mensajes m JOIN conversaciones c ON c.id=m.conversacion_id WHERE c.id=? AND c.usuario_id=? ORDER BY m.id DESC LIMIT 20',[$id,usuario()])->fetchAll();
    $entrada=[]; $restantes=28000;
    foreach ($filas as $m) {
        $contenido=mb_substr($m['mensaje'],0,5000);
        if ($m['emisor']==='bag' && $m['datos']) {
            $datos=json_decode($m['datos'],true) ?: [];
            if (!empty($datos['games'])) $contenido.="\n[Juegos mostrados en tarjetas, en orden: ".implode(', ',array_column($datos['games'],'nombre')).']';
        }
        if (mb_strlen($contenido)>$restantes) break;
        $restantes-=mb_strlen($contenido);
        $entrada[]=['role'=>$m['emisor']==='bag'?'assistant':'user','content'=>$contenido];
    }
    return array_reverse($entrada);
}
