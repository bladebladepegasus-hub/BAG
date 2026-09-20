<?php
require_once __DIR__.'/catalogo.php';
require_once __DIR__.'/tiendas_extra.php';

// Sólo orígenes de tienda fijos: nunca se solicita una URL aportada por el usuario.
function tiendas_json(array $urls): array {
    $multi=curl_multi_init(); $handles=[]; $resultado=[];
    foreach ($urls as $key=>$request) {
        $url=is_array($request)?$request['url']:$request;
        if (!in_array(parse_url($url,PHP_URL_HOST),['store.steampowered.com','catalog.gog.com','api.gog.com','displaycatalog.mp.microsoft.com','searching.nintendo-europe.com','ihjzq5lw2r-dsn.algolia.net'],true)) throw new InvalidArgumentException('Fuente no permitida.');
        $c=curl_init($url);
        curl_setopt_array($c,[CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>5,CURLOPT_TIMEOUT=>18,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_USERAGENT=>'BAG/1.0 (game catalog)',CURLOPT_ENCODING=>'']);
        if (is_array($request)) curl_setopt_array($c,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>json_encode($request['body']),CURLOPT_HTTPHEADER=>$request['headers']]);
        curl_multi_add_handle($multi,$c); $handles[$key]=$c;
    }
    do { $status=curl_multi_exec($multi,$running); if ($running) curl_multi_select($multi,0.5); } while ($running && $status===CURLM_OK);
    foreach ($handles as $key=>$c) {
        $data=json_decode(curl_multi_getcontent($c),true);
        $resultado[$key]=curl_getinfo($c,CURLINFO_HTTP_CODE)===200 && is_array($data) ? $data : null;
        curl_multi_remove_handle($multi,$c); curl_close($c);
    }
    curl_multi_close($multi); return $resultado;
}
function titulo_tienda(string $s): string {
    return trim(preg_replace('/[^\p{L}\p{N}]+/u',' ',normalizar(str_replace(['™','®','©'],'',$s))));
}
function coincide_titulo(string $titulo,string $q): bool {
    $palabras=explode(' ',titulo_tienda($titulo));
    foreach (explode(' ',titulo_tienda($q)) as $p) if ($p!=='' && !in_array($p,$palabras,true)) return false;
    return true;
}
function base_online(string $id,string $nombre,string $fuente): array {
    return ['id'=>$id,'nombre'=>$nombre,'descripcion'=>'','generos'=>[],'plataformas'=>['PC'],'clasificacion'=>'Sin clasificación','edad_minima'=>null,'fecha'=>'No disponible','imagen'=>'../css/portada.svg','multijugador'=>null,'solo'=>null,'similares'=>[],'palabras_clave'=>'','fuente'=>$fuente,'nota'=>'Datos consultados directamente en la tienda. Cobertura de esta ficha: PC.','actualizacion'=>date(DATE_ATOM),'precio'=>null,'moneda'=>null,'url'=>null];
}
function juego_steam(array $d,?array $mercado=null): array {
    $mercado??=mercado_actual();
    $j=base_online('steam-'.(int)$d['steam_appid'],$d['name'],'Steam');
    $j['url']='https://store.steampowered.com/app/'.(int)$d['steam_appid'].'/?'.http_build_query(['cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']]);
    $j['descripcion']=html_entity_decode(strip_tags($d['short_description']??''),ENT_QUOTES|ENT_HTML5,'UTF-8');
    $j['imagen']=$d['header_image']??$j['imagen'];
    $j['generos']=array_map(fn($g)=>$g==='Rol'?'RPG':$g,array_column($d['genres']??[],'description'));
    $j['fecha']=$d['release_date']['date']??'No disponible';
    $j['tipo']=$d['type']??'game';
    if (isset($d['metacritic']['score'])) $j['critica']=(int)$d['metacritic']['score'];
    $categorias=array_column($d['categories']??[],'id');
    $j['solo']=in_array(2,$categorias); $j['multijugador']=(bool)array_intersect([1,9,36,38,49],$categorias);
    // required_age=0 no equivale a una clasificación infantil verificada.
    if ((int)($d['required_age']??0)>0) { $j['edad_minima']=(int)$d['required_age']; $j['clasificacion']='Edad mínima de la tienda: '.$j['edad_minima'].'+'; }
    $esrb=strtoupper($d['ratings']['esrb']['rating']??'');
    if (isset(['E'=>0,'E10'=>10,'E10+'=>10,'T'=>13,'M'=>17,'AO'=>18][$esrb])) { $j['edad_minima']=['E'=>0,'E10'=>10,'E10+'=>10,'T'=>13,'M'=>17,'AO'=>18][$esrb];$j['clasificacion']='ESRB '.$esrb; }
    if (isset($d['price_overview']['final'])) { $j['precio']=$d['price_overview']['final']/100; $j['moneda']=$d['price_overview']['currency']; }
    elseif (!empty($d['is_free'])) { $j['precio']=0; $j['moneda']=$mercado['moneda']; }
    $j['region']=$mercado['nombre'];$j['region_verificada']=$j['precio']!==null;
    return $j;
}
function juego_gog(array $d,?array $mercado=null): array {
    $mercado??=mercado_actual();
    $j=base_online('gog-'.$d['id'],$d['title'],'GOG');
    $j['imagen']=$d['coverHorizontal']??$j['imagen'];
    $j['url']=$d['storeLink']??null;
    $j['generos']=array_column($d['genres']??[],'name');
    $j['fecha']=$d['releaseDate']??'No disponible';
    $j['tipo']=$d['productType']??'game';
    $j['descripcion']=implode(' · ',array_merge($d['developers']??[],$j['generos']));
    $features=array_column($d['features']??[],'slug');
    $j['solo']=in_array('single',$features); $j['multijugador']=in_array('multi',$features);
    if (isset($d['price']['finalMoney']['amount']) && is_numeric($d['price']['finalMoney']['amount'])) {
        $j['precio']=(float)$d['price']['finalMoney']['amount']; $j['moneda']=$d['price']['finalMoney']['currency'];
    }
    $j['region']=$mercado['nombre'];$j['region_verificada']=$j['precio']!==null;
    return $j;
}
function buscar_online(string $q,array $f=[]): array {
    $mercado=mercado_actual();
    $descubrir=!empty($f['descubrir']);
    if ($q==='' && !$descubrir) return ['games'=>[],'warnings'=>['Escribe un título para consultar las cinco fuentes conectadas.'],'checked_at'=>null,'sources'=>[]];
    $plataforma=$f['plataforma']??'';
    $urls=[];
    if ($plataforma===''||$plataforma==='PC') {
        $urls['Steam']=$descubrir?'https://store.steampowered.com/api/featuredcategories?'.http_build_query(['cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']]):'https://store.steampowered.com/api/storesearch/?'.http_build_query(['term'=>$q,'l'=>$mercado['steam_idioma'],'cc'=>strtolower($mercado['region'])]);
        $params=['limit'=>32,'order'=>'desc:score','countryCode'=>$mercado['region'],'locale'=>'en-US','currencyCode'=>'USD'];
        if (!$descubrir) $params['query']='like:'.$q;
        // https://www.gog.com/en/news/priostanovka_prodaz_v_rossii_i_belarusi
        if($mercado['region']!=='RU')$urls['GOG']='https://catalog.gog.com/v1/catalog?'.http_build_query($params);
    }
    if ($plataforma===''||in_array($plataforma,['Xbox','PC'])) $urls['Xbox']='https://displaycatalog.mp.microsoft.com/v7.0/productFamilies/games/products?'.http_build_query(['query'=>$descubrir?'':$q,'market'=>$mercado['region'],'languages'=>$mercado['locale'],'platform'=>'Windows.Xbox','top'=>10]);
    if ($mercado['region']==='ES' && ($plataforma===''||in_array($plataforma,['Nintendo Switch','Nintendo Switch 2']))) $urls['Nintendo']=url_nintendo($q,$descubrir);
    $urls['Eneba']=solicitud_eneba($descubrir?'':$q,$mercado);
    if (!empty($f['tienda'])) {
        if (!in_array($f['tienda'],['Steam','GOG','Xbox','Nintendo','Eneba'],true)) throw new InvalidArgumentException('Selecciona una tienda válida.');
        $urls=array_intersect_key($urls,[$f['tienda']=>true]);
        if (!$urls) return ['games'=>[],'warnings'=>['La tienda seleccionada no tiene cobertura verificable para tu país y plataforma.'],'checked_at'=>null,'sources'=>[['name'=>$f['tienda'],'status'=>'sin cobertura regional']]];
    }
    $r=tiendas_json($urls);$juegos=[];$warnings=$mercado['region']==='ES'?[]:['Nintendo: el conector actual sólo verifica España; se omite para tu región.'];$sources=[];$details=[];
    $paths=['Steam'=>$descubrir?'top_sellers':'items','GOG'=>'products','Xbox'=>'Products','Nintendo'=>'response','Eneba'=>'hits'];
    foreach ($urls as $name=>$_) {
        $ok=isset($r[$name][$paths[$name]]);
        $sources[]=['name'=>$name,'status'=>$ok?'consultada':'no disponible'];
        if (!$ok) $warnings[]=$name.' no respondió; no se utilizaron datos de ejemplo.';
    }
    if (!array_filter($sources,fn($s)=>$s['status']==='consultada')) responder(['success'=>false,'message'=>'Las fuentes no respondieron. Inténtalo de nuevo más tarde.'],502);
    $steamItems=$descubrir?array_slice($r['Steam']['top_sellers']['items']??[],0,10):($r['Steam']['items']??[]);
    foreach ($steamItems as $d) {
        if (!$descubrir && (($d['type']??'')!=='app' || !coincide_titulo($d['name'],$q))) continue;
        $id=(int)$d['id'];$details['steam-'.$id]='https://store.steampowered.com/api/appdetails?'.http_build_query(['appids'=>$id,'cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']]);
    }
    $xids=[];
    foreach ($r['Xbox']['Products']??[] as $d) if ($descubrir||coincide_titulo($d['LocalizedProperties'][0]['ProductTitle']??'',$q)) $xids[]=$d['ProductId'];
    if ($xids) $details['Xbox']='https://displaycatalog.mp.microsoft.com/v7.0/products?'.http_build_query(['bigIds'=>implode(',',array_slice($xids,0,10)),'market'=>$mercado['region'],'languages'=>$mercado['locale']]);
    foreach (tiendas_json($details) as $id=>$data) {
        if ($id==='Xbox') {
            if (!isset($data['Products'])) $warnings[]='No se pudieron actualizar las fichas de Xbox.';
            foreach ($data['Products']??[] as $d) $juegos=array_merge($juegos,juegos_xbox($d,$mercado));
        } else {
            $appid=substr($id,6);
            if (!empty($data[$appid]['success'])) $juegos[]=juego_steam($data[$appid]['data'],$mercado);
            else $warnings[]='Se omitió una ficha de Steam que no respondió.';
        }
    }
    foreach ($r['GOG']['products']??[] as $d) if ($descubrir||coincide_titulo($d['title'],$q)) $juegos[]=juego_gog($d,$mercado);
    foreach ($r['Nintendo']['response']['docs']??[] as $d) {
        if (!array_intersect(['Nintendo Switch','Nintendo Switch 2'],$d['system_names_txt']??[])) continue;
        if ($descubrir||coincide_titulo($d['title'],$q)) $juegos[]=juego_nintendo($d);
    }
    foreach ($r['Eneba']['hits']??[] as $d) {
        $j=juego_eneba($d,$mercado);if ($descubrir||coincide_titulo($j['nombre'],$q)) $juegos[]=$j;
    }
    $sinVerificar=count(array_filter($juegos,fn($j)=>empty($j['region_verificada'])));
    $juegos=array_values(array_filter($juegos,fn($j)=>!empty($j['region_verificada'])));
    if($sinVerificar)$warnings[]='Se omitieron ofertas sin disponibilidad confirmada para '.$mercado['nombre'].'.';
    $juegos=array_values(array_filter($juegos,fn($j)=>$plataforma===''||in_array($plataforma,$j['plataformas'])));
    if (!empty($f['genero'])) $juegos=array_values(array_filter($juegos,fn($j)=>genero_compatible($j,$f['genero'])));
    usort($juegos,fn($a,$b)=>(titulo_tienda($b['nombre'])===titulo_tienda($q))<=>(titulo_tienda($a['nombre'])===titulo_tienda($q)));
    return ['games'=>$juegos,'warnings'=>array_values(array_unique($warnings)),'checked_at'=>date(DATE_ATOM),'sources'=>$sources];
}
function genero_compatible(array $j,string $genero): bool {
    $map=['Acción'=>['action','accion'],'Aventura'=>['adventure','aventura'],'RPG'=>['rpg','rol','role playing'],'Terror'=>['horror','terror'],'Carreras'=>['racing','carreras'],'Deportes'=>['sports','deportes'],'Estrategia'=>['strategy','estrategia'],'Shooter'=>['shooter','disparos'],'Sandbox'=>['sandbox'],'Supervivencia'=>['survival','supervivencia'],'Puzzle'=>['puzzle','puzle','puzles']];
    $hay=normalizar(implode(' ', $j['generos']));
    foreach ($map[$genero]??[normalizar($genero)] as $g) if(str_contains($hay,$g)) return true;
    return false;
}
function detalle_online(string $id,string $q=''): ?array {
    $mercado=mercado_actual();
    if (preg_match('/^steam-(\d+)$/',$id,$m)) {
        $r=tiendas_json(['d'=>'https://store.steampowered.com/api/appdetails?'.http_build_query(['appids'=>$m[1],'cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']])]);
        if(empty($r['d'][$m[1]]['success']))return null;
        $j=juego_steam($r['d'][$m[1]]['data'],$mercado);return !empty($j['region_verificada'])?$j:null;
    }
    if (preg_match('/^(gog|xbox|nintendo|eneba)-[a-zA-Z0-9-]+$/',$id) && $q!=='') {
        foreach (buscar_online($q)['games'] as $j) if ($j['id']===$id) return $j;
    }
    return null;
}
function agrupar_precios_online(array $juegos): array {
    $grupos=[];
    foreach ($juegos as $j) {
        // Ediciones distintas permanecen separadas; nunca mezclar monedas para escoger ganador.
        $key=titulo_tienda($j['nombre']);
        if (!isset($grupos[$key])) $grupos[$key]=['juego'=>$j['nombre'],'imagen'=>$j['imagen'],'clasificacion'=>$j['clasificacion'],'advertencia'=>$j['advertencia']??'','fuente'=>'Tiendas y marketplaces','actualizacion'=>$j['actualizacion'],'nota'=>'Consulta región, plataforma y formato en cada oferta. Los precios entre monedas o condiciones distintas no se comparan directamente.','filas'=>[],'mejor_precio'=>null];
        $grupos[$key]['filas'][]=['plataforma'=>implode(' / ',$j['plataformas']),'region'=>$j['region']??'México','formato'=>$j['formato']??'Digital','condiciones'=>$j['nota'],'precio_desde'=>$j['precio_desde']??false,'tienda'=>$j['fuente'],'disponible'=>true,'precio'=>$j['precio'],'moneda'=>$j['moneda'],'url'=>$j['url']];
    }
    return array_values($grupos);
}
