<?php
// Adaptadores de catálogos públicos. No se utilizan credenciales privadas de terceros.
// Mapa publicado por la web de Eneba, comprobado el 13/09/2026 en main.a5b831c49fe25d9fe643.bundle.js.
function pais_eneba(string $region): array {
    return ['MX'=>[140,'mexico'],'US'=>[234,'united_states'],'CA'=>[39,'canada'],'AR'=>[10,'argentina'],'BR'=>[30,'brazil'],'CL'=>[44,'chile'],'CO'=>[48,'colombia'],'PE'=>[171,'peru'],'UY'=>[236,'south_america'],'EC'=>[63,'ecuador'],'CR'=>[51,'south_america'],'ES'=>[207,'spain'],'GB'=>[233,'united_kingdom'],'DE'=>[80,'germany'],'FR'=>[74,'france'],'RU'=>[181,'russia']][$region]??[null,null];
}
function eneba_disponible(array $d,string $region):bool {
    [$id,$slug]=pais_eneba($region);
    if($id===null || !is_array($d['countryIds']??null) || !is_array($d['regionBlacklist']??null))return false;
    if(!in_array($id,array_map('intval',$d['countryIds']),true))return false;
    $excluidos=array_map(fn($s)=>strtolower((string)$s),$d['regionBlacklist']);
    return !in_array($slug,$excluidos,true) && !in_array(strtolower($region),$excluidos,true);
}
function solicitud_eneba(string $q,?array $mercado=null): array {
    $mercado??=mercado_actual();[$id,$slug]=pais_eneba($mercado['region']);
    return ['url'=>'https://ihjzq5lw2r-dsn.algolia.net/1/indexes/products_global/query',
        // Identificadores públicos de búsqueda publicados en el JavaScript de Eneba.
        'headers'=>['Content-Type: application/json','X-Algolia-Application-Id: IHJZQ5LW2R','X-Algolia-API-Key: 53864095e814940ffed0f69a897331f1'],
        'body'=>['query'=>$q,'hitsPerPage'=>32,'analytics'=>false,'filters'=>'productType:game AND stockAvailable:true AND countryIds:'.(int)$id.' AND NOT regionBlacklist:"'.$slug.'"']];
}
function url_nintendo(string $q,bool $descubrir=false): string {
    $tokens=preg_split('/\s+/u',titulo_tienda($q),-1,PREG_SPLIT_NO_EMPTY);
    $consulta=$descubrir?'*:*':implode(' AND ',array_map(fn($t)=>'"'.$t.'"',$tokens));
    return 'https://searching.nintendo-europe.com/es/select?'.http_build_query(['q'=>$consulta,'fq'=>$descubrir?'type:GAME AND date_from:[* TO NOW] AND price_lowest_f:[0 TO *]':'type:GAME','rows'=>80,'wt'=>'json','sort'=>$descubrir?'date_from desc':'score desc']);
}
function juego_nintendo(array $d): array {
    $j=base_online('nintendo-'.$d['fs_id'],$d['title'],'Nintendo');
    $j['plataformas']=$d['system_names_txt']??[];
    $j['imagen']=$d['image_url_h2x1_s']??$d['image_url_sq_s']??$d['image_url']??$j['imagen'];
    $j['url']='https://www.nintendo.com'.$d['url'];
    $j['descripcion']=html_entity_decode(strip_tags($d['excerpt']??$d['product_catalog_description_s']??''),ENT_QUOTES|ENT_HTML5,'UTF-8');
    $j['generos']=$d['pretty_game_categories_txt']??[];
    $j['fecha']=$d['pretty_date_s']??'No disponible';
    $j['region']='España';$j['region_verificada']=isset($d['price_lowest_f']) && $d['price_lowest_f']>=0 && empty($d['eshop_removed_b']);$j['formato']='Variantes de la ficha Nintendo';$j['tipo']='game';$j['precio_desde']=true;
    $j['solo']=isset($d['players_from'])?(int)$d['players_from']===1:null;
    $j['multijugador']=isset($d['players_to'])?(int)$d['players_to']>1:null;
    if (isset($d['age_rating_value']) && is_numeric($d['age_rating_value'])) { $j['edad_minima']=(int)$d['age_rating_value'];$j['clasificacion']=$d['pretty_agerating_s']??'PEGI '.$j['edad_minima']; }
    if (isset($d['price_lowest_f']) && $d['price_lowest_f']>=0 && empty($d['eshop_removed_b'])) { $j['precio']=(float)$d['price_lowest_f'];$j['moneda']='EUR'; }
    $j['popularidad']=(float)($d['hits_i']??0);
    $j['nota']='Precio mínimo de las variantes de la ficha Nintendo España. Puede corresponder a un paquete de mejora que requiere el juego base. Verifica el contenido: no se presenta como precio del juego completo ni confirma compra en México.';
    return $j;
}
function juegos_xbox(array $d,?array $mercado=null): array {
    $mercado??=mercado_actual();
    $l=$d['LocalizedProperties'][0]??[];
    if (empty($l['ProductTitle'])) return [];
    $base=base_online('xbox-'.$d['ProductId'],$l['ProductTitle'],'Xbox');
    $base['url']='https://www.xbox.com/'.$mercado['locale'].'/games/store/-/'.$d['ProductId'];
    $base['descripcion']=html_entity_decode(strip_tags($l['ShortDescription']??$l['ProductDescription']??''),ENT_QUOTES|ENT_HTML5,'UTF-8');
    foreach (['TitledHeroArt','SuperHeroArt','BoxArt','Poster'] as $purpose) {
        foreach ($l['Images']??[] as $im) if (($im['ImagePurpose']??'')===$purpose) { $base['imagen']=(str_starts_with($im['Uri'],'//')?'https:':'').$im['Uri'];break 2; }
    }
    $base['region']=$mercado['nombre'];$base['region_verificada']=false;$base['formato']='Digital';$base['tipo']=($d['ProductKind']??'')==='Game'?'game':'dlc';
    $base['generos']=array_values(array_filter([$d['Properties']['Category']??'']));
    $base['fecha']=$d['MarketProperties'][0]['OriginalReleaseDate']??'No disponible';
    $attr=array_column($d['Properties']['Attributes']??[],'Name');
    $base['solo']=in_array('SinglePlayer',$attr);$base['multijugador']=(bool)array_intersect(['XblOnlineMultiPlayer','XblLocalMultiPlayer','XblCrossPlatformMultiPlayer'],$attr);
    foreach ($d['MarketProperties'][0]['ContentRatings']??[] as $rating) {
        if (($rating['RatingSystem']??'')!=='ESRB') continue;
        $ages=['ESRB:E'=>0,'ESRB:E10'=>10,'ESRB:E10+'=>10,'ESRB:T'=>13,'ESRB:M'=>17,'ESRB:AO'=>18];
        $base['edad_minima']=$ages[$rating['RatingId']]??null;$base['clasificacion']=$rating['RatingId'];break;
    }
    foreach ($d['MarketProperties'][0]['UsageData']??[] as $u) if (($u['AggregateTimeSpan']??'')==='AllTime') { $base['valoracion']=$u['AverageRating']??null;$base['votos']=$u['RatingCount']??0; }
    $base['nota']='Oferta de compra de Xbox para '.$mercado['nombre'].'. Las ofertas condicionadas a suscripciones o propiedad previa se excluyen del precio.';
    $result=[];
    foreach ($d['DisplaySkuAvailabilities']??[] as $entry) {
        $sku=$entry['Sku']??[];
        if (($sku['SkuType']??'')!=='full' || !empty($sku['Properties']['IsTrial']) || !empty($sku['RecurrencePolicy'])) continue;
        $offers=[];
        foreach ($entry['Availabilities']??[] as $a) {
            if (!in_array('Purchase',$a['Actions']??[],true) || !empty($a['RemediationRequired']) || !empty($a['Remediations']) || !empty($a['Conditions']['EligibilityConditions'])) continue;
            if (!in_array($mercado['region'],$a['Markets']??[],true)) continue;
            $start=strtotime($a['Conditions']['StartDate']??'1970-01-01');$end=strtotime($a['Conditions']['EndDate']??'2100-01-01');
            if ($start>time()||$end<time()) continue;
            $p=$a['OrderManagementData']['Price']??[];
            if (!isset($p['ListPrice']) || !preg_match('/^[A-Z]{3}$/',$p['CurrencyCode']??'')) continue;
            $offers[]=$a;
        }
        if (!$offers) continue;
        usort($offers,fn($a,$b)=>($a['DisplayRank']??999)<=>($b['DisplayRank']??999));
        $a=$offers[0];$j=$base;
        $j['id'].='-'.$sku['SkuId'];$j['nombre']=($sku['LocalizedProperties'][0]['SkuTitle']??'')?:$base['nombre'];
        $bundled=count($sku['Properties']['BundledSkus']??[]);
        $j['formato']=!empty($sku['Properties']['IsBundle'])?'Paquete digital ('.$bundled.' productos)':'Juego digital';
        $j['contenido']=html_entity_decode(strip_tags($sku['LocalizedProperties'][0]['SkuDescription']??$base['descripcion']),ENT_QUOTES|ENT_HTML5,'UTF-8');
        $j['nota'].=' '.$j['formato'].'. Consulta el contenido incluido en la ficha original.';
        $j['precio']=(float)$a['OrderManagementData']['Price']['ListPrice'];$j['moneda']=$a['OrderManagementData']['Price']['CurrencyCode'];$j['region_verificada']=true;$j['plataformas']=[];
        foreach ($a['Conditions']['ClientConditions']['AllowedPlatforms']??[] as $platform) {
            if ($platform['PlatformName']==='Windows.Xbox') $j['plataformas'][]='Xbox';
            if ($platform['PlatformName']==='Windows.Desktop') $j['plataformas'][]='PC';
        }
        if (!$j['plataformas']) continue;
        $result[]=$j;
    }
    if (!$result) { $base['plataformas']=!empty($d['Properties']['XboxConsoleGenCompatible'])?['Xbox']:[];$result[]=$base; }
    return $result;
}
function juego_eneba(array $d,?array $mercado=null): array {
    $mercado??=mercado_actual();
    $t=$d['translations'][['es'=>'es_ES','en'=>'en_US','ru'=>'ru_RU'][$mercado['idioma']]]??$d['translations']['en_US']??[];
    $j=base_online('eneba-'.$d['objectID'],$t['name']??$d['slug'],'Eneba');
    $j['imagen']=$d['images']['cover300']['src2x']??$d['images']['cover300']['src']??$j['imagen'];
    $j['url']='https://www.eneba.com/'.$d['slug'];$j['region']=strtoupper(implode(', ',$d['productRegions']??[]))?:'No especificada';
    $j['plataformas']=[];
    foreach ($d['worksOn']??[] as $p) {
        if (preg_match('/WINDOWS|MAC|LINUX/i',$p)) $j['plataformas'][]='PC';
        elseif (stripos($p,'XBOX')!==false) $j['plataformas'][]='Xbox';
        elseif (preg_match('/PLAYSTATION|PS[345]/i',$p)) $j['plataformas'][]='PlayStation';
        elseif (preg_match('/SWITCH.?2/i',$p)) $j['plataformas'][]='Nintendo Switch 2';
        elseif (preg_match('/SWITCH|NINTENDO/i',$p)) $j['plataformas'][]='Nintendo Switch';
    }
    $j['plataformas']=array_values(array_unique($j['plataformas']));
    if (str_contains($j['nombre'],'Nintendo Switch 2')) $j['plataformas']=array_values(array_unique(array_merge(array_diff($j['plataformas'],['Nintendo Switch']),['Nintendo Switch 2'])));
    $j['generos']=array_map(fn($g)=>ucfirst(str_replace(['-games','-'],['',' '],$g)),$d['genres']??[]);
    $j['descripcion']=$t['descriptionTitle']??$j['nombre'];
    $j['fecha']=isset($d['releasedAtUnix'])?date('Y-m-d',$d['releasedAtUnix']):'No disponible';
    $j['tipo']=$d['productType']??'game';$j['formato']='Oferta de marketplace · '.$d['drmName'];
    $j['precio_desde']=true;
    $j['solo']=in_array('single-player-games',$d['genres']??[]);$j['multijugador']=(bool)array_intersect(['multiplayer-games','co-op-games'],$d['genres']??[]);
    // Precio visible base; se omiten promociones con condiciones no verificadas.
    $price=!empty($d['promotionAvailable'])?($d['lowestPriceBeforeDiscount'][$mercado['moneda']]??null):($d['lowestPrice'][$mercado['moneda']]??null);
    if (is_numeric($price) && !empty($d['stockAvailable'])) { $j['precio']=$price/100;$j['moneda']=$mercado['moneda']; }
    $j['region_verificada']=eneba_disponible($d,$mercado['region']) && $j['precio']!==null;
    $j['nota']='Precio desde el índice público de Eneba, antes de posibles cargos. Revisa vendedor, tipo de entrega y países de activación; se muestran sólo ofertas con país de activación comprobado en el catálogo.';
    return $j;
}
