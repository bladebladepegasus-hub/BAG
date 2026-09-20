<?php
require_once __DIR__.'/catalogo_online.php';
function recomendar_online(array $f,string $similar=''): array {
    $r=buscar_online('', ['plataforma'=>$f['plataforma']??'','descubrir'=>true]);
    $base=null;
    if ($similar!=='') {
        $s=buscar_online($similar,['plataforma'=>$f['plataforma']??'']);
        foreach ($s['games'] as $j) if (titulo_tienda($j['nombre'])===titulo_tienda($similar)) { $base=$j;break; }
        $base=$base??($s['games'][0]??null);
        $r['warnings']=array_merge($r['warnings'],$s['warnings']);
        if (!$base) return ['games'=>[],'message'=>'No encontramos el juego de referencia en las fuentes consultadas. Escribe su título oficial.','sources'=>$r['sources']];
    }
    $candidatos=[];$maxima=clasificacion_valida($f['clasificacion_maxima']??'A');$modo=$f['modo_juego']??'Ambos';
    foreach ($r['games'] as $j) {
        if (!admite_clasificacion($j,$maxima)) continue;
        if (!empty($f['genero'])&&!genero_compatible($j,$f['genero'])) continue;
        if ($modo==='Solo'&&$j['solo']!==true || $modo==='Multijugador'&&$j['multijugador']!==true) continue;
        if (!in_array($j['tipo']??'game',['game','pack'])) continue;
        $razones=['Clasificación '.$j['clasificacion'].' dentro de tu selección '.$maxima];$score=0;
        if ($base) {
            if(titulo_tienda($j['nombre'])===titulo_tienda($base['nombre']))continue;
            $comunes=array_intersect(array_map('normalizar',$j['generos']),array_map('normalizar',$base['generos']));
            if (!$comunes)continue;
            $score+=count($comunes)*10;$razones[]='Comparte género con '.$base['nombre'];
        }
        if (!empty($f['genero'])) {$score+=10;$razones[]='Coincide con '.$f['genero'];}
        if (!empty($f['plataforma'])) $razones[]='Disponible para '.$f['plataforma'];
        if ($modo!=='Ambos') $razones[]='Modo '.$modo.' publicado por la tienda';
        if (isset($j['valoracion']) && ($j['votos']??0)>=10) { $score+=(float)$j['valoracion'];$razones[]=$j['valoracion'].'/5 en Xbox ('.$j['votos'].' valoraciones)'; }
        $j['motivo']=implode(' · ',$razones);$j['_score']=$score;$candidatos[]=$j;
    }
    usort($candidatos,fn($a,$b)=>$b['_score']<=>$a['_score']);
    $unicos=[];
    foreach ($candidatos as $j) {unset($j['_score']);$key=titulo_tienda($j['nombre']);if(!isset($unicos[$key]))$unicos[$key]=$j;}
    $games=array_slice(array_values($unicos),0,12);
    $message='Selección de los catálogos consultados según tus filtros y la clasificación publicada; no es un ranking universal.';
    if (!$games) $message='No encontramos recomendaciones con clasificación y filtros verificables en esta consulta. Prueba otros filtros; no se añaden juegos demo ni se incluyen los de clasificación desconocida.';
    $message.=' Clasificación máxima: '.$maxima.'.';
    if ($r['warnings']) $message.=' '.implode(' ',$r['warnings']);
    return ['games'=>advertir_clasificacion($games,$maxima),'message'=>$message,'sources'=>$r['sources'],'checked_at'=>$r['checked_at']];
}
