<?php
require_once __DIR__.'/recomendador_online.php';
$f=perfil();
foreach(['clasificacion_maxima','plataforma','genero','modo_juego'] as $k) if(isset($_GET[$k])) $f[$k]=texto($_GET[$k],40);
$f['clasificacion_maxima']=clasificacion_valida($f['clasificacion_maxima']);
validar_filtros($f);
responder(['success'=>true]+recomendar_online($f,texto($_GET['similar']??'')));
