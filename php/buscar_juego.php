<?php
require_once __DIR__.'/catalogo_online.php';
$q=texto($_GET['q'] ?? '');
$f=['plataforma'=>texto($_GET['plataforma'] ?? '',40),'genero'=>texto($_GET['genero'] ?? '',40),'tienda'=>texto($_GET['tienda']??'',20)];
validar_filtros($f);
registrar_busqueda($q,'buscar');
$r=buscar_online($q,$f);
$r['games']=advertir_clasificacion($r['games'],perfil()['clasificacion_maxima']);
responder(['success'=>true]+$r);
