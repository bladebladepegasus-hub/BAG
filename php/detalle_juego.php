<?php
require_once __DIR__.'/catalogo_online.php';
$id=texto($_GET['id'] ?? '',80);
$j=preg_match('/^(steam|gog|xbox|nintendo|eneba)-/',$id) ? detalle_online($id,texto($_GET['q']??'')) : null;
if (!$j) responder(['success'=>false,'message'=>'No encontré ese videojuego o la fuente no está disponible.'],404);
responder(['success'=>true,'game'=>advertir_clasificacion([$j],perfil()['clasificacion_maxima'])[0]]);
