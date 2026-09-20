<?php
require_once __DIR__.'/comun.php';
require_once __DIR__.'/datos_demo.php';
require_once __DIR__.'/filtro_edad.php';
function rawg(string $ruta, array $parametros=[]): ?array {
    global $RAWG_API_KEY;
    if (!$RAWG_API_KEY || !function_exists('curl_init')) return null;
    $c = curl_init('https://api.rawg.io/api/'.$ruta.'?'.http_build_query($parametros+['key'=>$RAWG_API_KEY]));
    curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>8,CURLOPT_FOLLOWLOCATION=>false]);
    $r=curl_exec($c); $status=curl_getinfo($c,CURLINFO_HTTP_CODE); curl_close($c);
    if ($status !== 200 || !$r) return null;
    $d=json_decode($r,true); return is_array($d) ? $d : null;
}
function juego_rawg(array $j): array {
    $clasificaciones=[1=>['E',0],2=>['E10+',10],3=>['T13+',13],4=>['M17+',17],5=>['AO18+',18]];
    [$cl,$edad]=$clasificaciones[$j['esrb_rating']['id'] ?? 0] ?? ['Sin clasificación',null];
    $plataformas=[];
    foreach ($j['platforms'] ?? [] as $p) {
        $n=$p['platform']['name'];
        $plataformas[]=str_contains($n,'Xbox') ? 'Xbox' : (str_contains($n,'PlayStation') ? 'PlayStation' : (in_array($n,['iOS','Android']) ? 'Mobile' : $n));
    }
    $generos=['Action'=>'Acción','Adventure'=>'Aventura','Racing'=>'Carreras','Shooter'=>'Shooter','Sports'=>'Deportes','Strategy'=>'Estrategia','Puzzle'=>'Puzzle','Simulation'=>'Simulación'];
    return ['id'=>'rawg-'.(int)$j['id'],'nombre'=>$j['name'],'descripcion'=>strip_tags($j['description_raw'] ?? $j['description'] ?? 'Consulta los detalles para ampliar la información.'),'generos'=>array_map(fn($g)=>$generos[$g['name']] ?? $g['name'],$j['genres'] ?? []),'plataformas'=>array_values(array_unique($plataformas)),'clasificacion'=>$cl,'edad_minima'=>$edad,'fecha'=>$j['released'] ?? 'No disponible','imagen'=>$j['background_image'] ?? '../css/portada.svg','multijugador'=>null,'solo'=>null,'palabras_clave'=>'','similares'=>[],'fuente'=>'RAWG','nota'=>'Información proporcionada por RAWG. Consulta la edición y región en la tienda.'];
}
function filtros_texto(string $q): array {
    $n=normalizar($q); $f=[];
    foreach (['PC','Xbox','PlayStation','Nintendo Switch','Mobile'] as $p) if (str_contains($n,normalizar($p))) $f['plataforma']=$p;
    foreach (['Acción','Aventura','RPG','Terror','Shooter','Carreras','Deportes','Estrategia','Sandbox','Supervivencia','Puzzle'] as $g) if (str_contains($n,normalizar($g))) $f['genero']=$g;
    if (preg_match('/\b(\d{1,3})\s*anos\b/',$n,$m)) $f['edad']=edad_valida($m[1]);
    if (str_contains($n,'multijugador') || str_contains($n,'con amigos')) $f['modo_juego']='Multijugador';
    elseif (preg_match('/\b(solo|un jugador)\b/',$n)) $f['modo_juego']='Solo';
    return $f;
}
function buscar_catalogo(string $q='', array $f=[]): array {
    $n=normalizar($q); $f=array_merge(filtros_texto($q),$f);
    $nombre=[];
    foreach(datos_demo() as $j) if ($n !== '' && (str_contains($n,normalizar($j['nombre'])) || str_contains(normalizar($j['nombre']),$n))) $nombre[]=$j;
    $palabras=preg_split('/\W+/u',$n,-1,PREG_SPLIT_NO_EMPTY);
    $ignorar=['busca','buscar','juegos','juego','videojuegos','videojuego','de','para','en','un','una','quiero','recomiendame','algo','me','gustan','los','las','informacion','sobre'];
    $palabras=array_diff($palabras,$ignorar);
    foreach (array_merge([$f['plataforma'] ?? '',$f['genero'] ?? '']) as $valor) $palabras=array_diff($palabras,explode(' ',normalizar($valor)));
    $lista=$nombre ?: datos_demo();
    $lista=array_values(array_filter($lista,function($j) use($nombre,$palabras,$f) {
        if (!empty($f['plataforma']) && !in_array($f['plataforma'],$j['plataformas'])) return false;
        if (!empty($f['genero']) && !in_array($f['genero'],$j['generos'])) return false;
        if ($nombre || !$palabras) return true;
        $hay=normalizar($j['nombre'].' '.$j['palabras_clave'].' '.implode(' ',$j['generos']).' '.implode(' ',$j['plataformas']));
        foreach ($palabras as $p) if (!str_contains($hay,$p)) return false;
        return true;
    }));
    global $RAWG_API_KEY;
    if ($RAWG_API_KEY) {
        $r=rawg('games',['search'=>$q,'page_size'=>40]);
        if ($r !== null && isset($r['results'])) {
            $lista=array_map('juego_rawg',$r['results']);
            $lista=array_values(array_filter($lista,fn($j)=>(empty($f['plataforma']) || in_array($f['plataforma'],$j['plataformas'])) && (empty($f['genero']) || in_array($f['genero'],$j['generos']))));
        }
    }
    return $lista;
}
function obtener_juego(string $id): ?array {
    if (preg_match('/^rawg-(\d+)$/',$id,$m)) { $r=rawg('games/'.$m[1]); return $r ? juego_rawg($r) : null; }
    foreach(datos_demo() as $j) if ((string)$j['id']===$id) return $j;
    return null;
}
function recomendar(array $f, string $similar='', string $gustos=''): array {
    $juegos=buscar_catalogo($gustos,['plataforma'=>$f['plataforma'] ?? '', 'genero'=>$f['genero'] ?? '']);
    $juegos=filtrar_clasificacion($juegos,clasificacion_valida($f['clasificacion_maxima']??'A'));
    $modo=$f['modo_juego'] ?? 'Ambos';
    $juegos=array_values(array_filter($juegos,fn($j)=>$modo==='Ambos' || ($modo==='Solo' ? $j['solo']===true : $j['multijugador']===true)));
    if ($similar !== '') {
        $base=buscar_catalogo($similar)[0] ?? null;
        if (!$base) return [];
        $juegos=array_values(array_filter($juegos,fn($j)=>$j['id']!==$base['id'] && (in_array($j['id'],$base['similares']) || count(array_intersect($j['generos'],$base['generos']))>0)));
        usort($juegos,fn($a,$b)=>(in_array($b['id'],$base['similares'])?10:0)+count(array_intersect($b['generos'],$base['generos']))-((in_array($a['id'],$base['similares'])?10:0)+count(array_intersect($a['generos'],$base['generos']))));
    }
    return array_slice($juegos,0,9);
}
function precios(array $j): array {
    $tiendas=['PC'=>['Steam','https://store.steampowered.com/search/?term='],'Xbox'=>['Xbox Store','https://www.xbox.com/es-MX/search?q='],'PlayStation'=>['PlayStation Store','https://store.playstation.com/es-mx/search/'],'Nintendo Switch'=>['Nintendo','https://www.nintendo.com/us/search/#q='],'Mobile'=>['Google Play','https://play.google.com/store/search?c=apps&q=']];
    $filas=[];
    foreach($tiendas as $p=>$t) $filas[]=['plataforma'=>$p,'tienda'=>$t[0],'disponible'=>in_array($p,$j['plataformas']),'precio'=>null,'moneda'=>null,'url'=>in_array($p,$j['plataformas'])?$t[1].rawurlencode($j['nombre']):null];
    return ['juego'=>$j['nombre'],'filas'=>$filas,'mejor_precio'=>null,'actualizacion'=>date(DATE_ATOM),'nota'=>'Precio no disponible actualmente. No hay una fuente de precios conectada. Los enlaces abren búsquedas en tiendas; verifica edición, plataforma y región.','fuente'=>$j['fuente']];
}
