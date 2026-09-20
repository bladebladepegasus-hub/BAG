const fs=require('fs');
function edit(p,fn){fs.writeFileSync(p,fn(fs.readFileSync(p,'utf8')));}
edit('php/.htaccess',s=>s.replace('config|config_local','configuracion_usuario|pruebas_region|config|config_local'));
edit('php/base_datos.sql',s=>s+"\nCREATE TABLE IF NOT EXISTS configuracion_usuario (usuario_id INT PRIMARY KEY, region CHAR(2) NOT NULL DEFAULT 'MX', idioma VARCHAR(2) NOT NULL DEFAULT 'es', tema VARCHAR(8) NOT NULL DEFAULT 'oscuro', FOREIGN KEY(usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE) ENGINE=InnoDB;\n");
edit('php/catalogo_online.php',s=>s
 .replace("function juego_steam(array $d): array {","function juego_steam(array $d,?array $mercado=null): array {\n    $mercado??=mercado_actual();")
 .replace(".'/?cc=mx&l=spanish'",".'/?'.http_build_query(['cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']])")
 .replace("$j['moneda']='MXN';","$j['moneda']=$mercado['moneda'];")
 .replace("    return $j;\n}\nfunction juego_gog", "    $j['region']=$mercado['nombre'];$j['region_verificada']=$j['precio']!==null;\n    return $j;\n}\nfunction juego_gog")
 .replace("function juego_gog(array $d): array {","function juego_gog(array $d,?array $mercado=null): array {\n    $mercado??=mercado_actual();")
 .replace("    return $j;\n}\nfunction buscar_online", "    $j['region']=$mercado['nombre'];$j['region_verificada']=$j['precio']!==null;\n    return $j;\n}\nfunction buscar_online")
 .replace("    $descubrir=!empty($f['descubrir']);", "    $mercado=mercado_actual();\n    $descubrir=!empty($f['descubrir']);")
 .replace("'https://store.steampowered.com/api/featuredcategories?cc=mx&l=spanish'", "'https://store.steampowered.com/api/featuredcategories?'.http_build_query(['cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']])")
 .replaceAll("'l'=>'spanish','cc'=>'mx'", "'l'=>$mercado['steam_idioma'],'cc'=>strtolower($mercado['region'])")
 .replace("'countryCode'=>'MX'", "'countryCode'=>$mercado['region']")
 .replaceAll("'market'=>'MX','languages'=>'es-MX'", "'market'=>$mercado['region'],'languages'=>$mercado['locale']")
 .replace("if ($plataforma===''||in_array($plataforma,['Nintendo Switch','Nintendo Switch 2']))", "if ($mercado['region']==='ES' && ($plataforma===''||in_array($plataforma,['Nintendo Switch','Nintendo Switch 2'])))")
 .replace("$r=tiendas_json($urls);$juegos=[];$warnings=[];", "$r=tiendas_json($urls);$juegos=[];$warnings=$mercado['region']==='ES'?[]:['Nintendo: el conector actual sólo verifica España; se omite para tu región.'];")
 .replaceAll("'cc'=>'mx','l'=>'spanish'", "'cc'=>strtolower($mercado['region']),'l'=>$mercado['steam_idioma']")
 .replace("juegos_xbox($d)","juegos_xbox($d,$mercado)")
 .replace("juego_steam($data[$appid]['data'])","juego_steam($data[$appid]['data'],$mercado)")
 .replace("juego_gog($d)","juego_gog($d,$mercado)")
 .replace("juego_eneba($d)","juego_eneba($d,$mercado)")
 .replace("    $juegos=array_values(array_filter($juegos,fn($j)=>$plataforma", "    $sinVerificar=count(array_filter($juegos,fn($j)=>empty($j['region_verificada'])));\n    $juegos=array_values(array_filter($juegos,fn($j)=>!empty($j['region_verificada'])));\n    if($sinVerificar)$warnings[]='Se omitieron ofertas sin disponibilidad confirmada para '.$mercado['nombre'].'.';\n    $juegos=array_values(array_filter($juegos,fn($j)=>$plataforma")
 .replace("function detalle_online(string $id,string $q=''): ?array {", "function detalle_online(string $id,string $q=''): ?array {\n    $mercado=mercado_actual();")
 .replace("return !empty($r['d'][$m[1]]['success']) ? juego_steam($r['d'][$m[1]]['data']) : null;", "if(empty($r['d'][$m[1]]['success']))return null;\n        $j=juego_steam($r['d'][$m[1]]['data'],$mercado);return !empty($j['region_verificada'])?$j:null;")
);
edit('php/tiendas_extra.php',s=>s
 .replace("$j['region']='España';", "$j['region']='España';$j['region_verificada']=isset($d['price_lowest_f']) && $d['price_lowest_f']>=0 && empty($d['eshop_removed_b']);")
 .replace("function juegos_xbox(array $d): array {", "function juegos_xbox(array $d,?array $mercado=null): array {\n    $mercado??=mercado_actual();")
 .replace("'https://www.xbox.com/es-MX/games/store/-/'", "'https://www.xbox.com/'.$mercado['locale'].'/games/store/-/'")
 .replace("$base['region']='México';", "$base['region']=$mercado['nombre'];$base['region_verificada']=false;")
 .replace("'Oferta de compra de Xbox para México. Las ofertas", "'Oferta de compra de Xbox para '.$mercado['nombre'].'. Las ofertas")
 .replace("in_array('MX',$a['Markets']", "in_array($mercado['region'],$a['Markets']")
 .replace("($p['CurrencyCode']??'')!=='MXN'", "!preg_match('/^[A-Z]{3}$/',$p['CurrencyCode']??'')")
 .replace("$j['moneda']='MXN';$j['plataformas']=[];", "$j['moneda']=$a['OrderManagementData']['Price']['CurrencyCode'];$j['region_verificada']=true;$j['plataformas']=[];")
 .replace("function juego_eneba(array $d): array {", "function juego_eneba(array $d,?array $mercado=null): array {\n    $mercado??=mercado_actual();")
 .replace("$t=$d['translations']['es_ES']", "$t=$d['translations'][['es'=>'es_ES','en'=>'en_US','ru'=>'ru_RU'][$mercado['idioma']]]")
 .replaceAll("['MXN']??null", "[$mercado['moneda']]??null")
 .replace("$j['moneda']='MXN';", "$j['moneda']=$mercado['moneda'];")
 .replace("    $j['nota']='Precio desde el índice", "    $j['region_verificada']=eneba_disponible($d,$mercado['region']) && $j['precio']!==null;\n    $j['nota']='Precio desde el índice")
 .replace("la región indicada no garantiza activación en México.", "se muestran sólo ofertas con país de activación comprobado en el catálogo.")
);
