<?php
require_once __DIR__.'/catalogo_online.php';
$q=texto($_GET['q'] ?? '');
if ($q==='') throw new InvalidArgumentException('Escribe el nombre de un videojuego.');
registrar_busqueda($q,'precios');
$f=['plataforma'=>texto($_GET['plataforma']??'',40),'tienda'=>texto($_GET['tienda']??'',20)];
validar_filtros($f);
$r=buscar_online($q,$f);
$r['games']=advertir_clasificacion($r['games'],perfil()['clasificacion_maxima']);
responder(['success'=>true,'prices'=>agrupar_precios_online($r['games']),'checked_at'=>$r['checked_at'],'sources'=>$r['sources'],'message'=>implode(' ',$r['warnings'])]);
