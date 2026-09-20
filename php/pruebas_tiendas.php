<?php
if (PHP_SAPI!=='cli') {http_response_code(404);exit;}
require_once __DIR__.'/catalogo_online.php';
$total=0;
function comprobar(bool $ok,string $nombre): void {global $total;if(!$ok)throw new RuntimeException($nombre);$total++;echo 'OK '.$nombre.PHP_EOL;}
$oferta=['Actions'=>['Purchase'],'Markets'=>['MX'],'Conditions'=>['StartDate'=>'2020-01-01','EndDate'=>'2099-01-01','ClientConditions'=>['AllowedPlatforms'=>[['PlatformName'=>'Windows.Xbox']]]],'OrderManagementData'=>['Price'=>['CurrencyCode'=>'MXN','ListPrice'=>599.99]],'DisplayRank'=>0];
$producto=['ProductId'=>'TESTPRODUCT','ProductKind'=>'Game','LocalizedProperties'=>[['ProductTitle'=>'Juego de prueba']],'Properties'=>['XboxConsoleGenCompatible'=>['ConsoleGen9']],'DisplaySkuAvailabilities'=>[['Sku'=>['SkuType'=>'full','SkuId'=>'0010','Properties'=>[]],'Availabilities'=>[$oferta]]]];
$j=juegos_xbox($producto)[0];
comprobar($j['precio']===599.99 && $j['plataformas']===['Xbox'],'Xbox conserva importe y plataforma de la oferta');
$condicionada=$oferta;$condicionada['RemediationRequired']=true;$condicionada['OrderManagementData']['Price']['ListPrice']=0;
$producto['DisplaySkuAvailabilities'][0]['Availabilities']=[$condicionada,$oferta];
comprobar(juegos_xbox($producto)[0]['precio']===599.99,'Cero condicionado a suscripción no reemplaza la compra');
$expirada=$oferta;$expirada['Conditions']['EndDate']='2020-01-01';
$producto['DisplaySkuAvailabilities'][0]['Availabilities']=[$expirada];
comprobar(juegos_xbox($producto)[0]['precio']===null,'Oferta expirada no se presenta como actual');
$extranjera=$oferta;$extranjera['Markets']=['US'];$producto['DisplaySkuAvailabilities'][0]['Availabilities']=[$extranjera];
comprobar(juegos_xbox($producto)[0]['precio']===null,'Oferta de otra región no se presenta como mexicana');
$n=juego_nintendo(['fs_id'=>'123','title'=>'Prueba Switch','url'=>'/es-es/Juegos/prueba.html','price_lowest_f'=>19.99]);
comprobar($n['precio_desde'] && $n['region']==='España' && $n['moneda']==='EUR','Nintendo identifica precio mínimo de variantes y región');
$n=juego_nintendo(['fs_id'=>'123','title'=>'Prueba','url'=>'/es-es/Juegos/prueba.html','price_lowest_f'=>-1]);
comprobar($n['precio']===null,'Sentinela Nintendo no se convierte en precio');
$e=['objectID'=>'test','slug'=>'game-test','translations'=>['en_US'=>['name'=>'Prueba (Nintendo Switch 2)']],'worksOn'=>['NINTENDO_SWITCH'],'drmName'=>'nintendo','stockAvailable'=>true,'lowestPrice'=>['MXN'=>12345],'productRegions'=>['europe']];
$j=juego_eneba($e);
comprobar($j['precio']===123.45 && $j['region']==='EUROPE','Eneba convierte centavos y conserva región');
comprobar($j['plataformas']===['Nintendo Switch 2'],'Switch 2 no se etiqueta como Switch original');
$e['promotionAvailable']=true;
comprobar(juego_eneba($e)['precio']===null,'Promoción Eneba sin precio base verificable no se publica');
comprobar(!coincide_titulo('Dark Souls','ark'),'Coincidencias por palabras evitan falsos positivos');
echo "$total pruebas de normalización correctas.\n";
