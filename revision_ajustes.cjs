const fs=require('fs');
const edit=(p,f)=>fs.writeFileSync(p,f(fs.readFileSync(p,'utf8')));
edit('js/vista_buscar.js',s=>s.replace('BAG.fuentes(d.sources);BAG.fuentes(d.sources);','BAG.fuentes(d.sources);'));
edit('js/vista_recomendaciones.js',s=>s.replace('const indicador=document.querySelector("#clasificacion-actual");if(indicador)indicador.textContent="Clasificación máxima: "+formulario.elements.clasificacion_maxima.value;',''));
edit('php/pruebas_ia.php',s=>{
 s=s.replace("$p=['edad'=>10,", "$p=['clasificacion_maxima'=>'A','region'=>'MX','idioma'=>'es',");
 const start=s.indexOf("    $resultado=ejecutar_herramienta_ia('consultar_catalogo'");
 const end=s.indexOf("    try { ejecutar_herramienta_ia('exec'",start);
 if(start<0||end<0)throw Error('No se encontró el bloque de pruebas antiguas');
 s=s.slice(0,start)+`    // El protocolo usa fixtures: los catálogos se comprueban en pruebas_revision.php --live.
    $resultado=['games'=>[['id'=>'steam-fixture','nombre'=>'Minecraft','descripcion'=>'Ficha de prueba de protocolo']]];
    $ejecutarFixture=fn($n,$a)=>$resultado;
    comprobar_ia(str_contains(instrucciones_ia(['idioma'=>'en','region'=>'US']),'Responde en inglés'),'Idioma del perfil en instrucciones');
    comprobar_ia(str_contains(instrucciones_ia(['idioma'=>'ru','region'=>'RU']),'"region":"RU"'),'Región guardada en contexto');
    comprobar_ia(in_array('Nintendo Switch 2',$herramientas[0]['parameters']['properties']['plataforma']['enum']),'Switch 2 admitida por herramientas');
`+s.slice(end);
 s=s.replace("fn($n,$a)=>ejecutar_herramienta_ia($n,$a,$p),$transporte", "$ejecutarFixture,$transporte");
 s=s.replace('Encontré Minecraft en el catálogo demo.','Encontré Minecraft en la consulta de prueba.');
 s=s.replace("    $contador=0; $peticiones=[];", "    consulta('DELETE FROM conversaciones WHERE id=?',[$otro]);\n    comprobar_ia(conversacion()!==$otro,'Chat recupera una conversación eliminada');\n    $contador=0; $peticiones=[];");
 return s;
});
edit('php/.htaccess',s=>s.replace('pruebas_region|','pruebas_revision|pruebas_region|'));
edit('.htaccess',s=>s.replace('\\.(sql|md)$','\\.(sql|md|cjs|ps1)$'));
edit('pruebas_multitienda.ps1',s=>s.replace("$r=Get-Bag 'buscar_juego.php?q=mario'",`$profile=Get-Bag 'preferencias.php'
$headers=@{'X-CSRF-Token'=$profile.csrf}
$prefs=@{region='ES';idioma='es';tema='oscuro';clasificacion_maxima='B';plataforma='';genero='';modo_juego='Ambos'}
Invoke-RestMethod -Uri 'http://localhost/BAG/php/guardar_preferencias.php' -Method Post -WebSession $bagSession -Headers $headers -ContentType 'application/json' -Body ($prefs|ConvertTo-Json) | Out-Null
$r=Get-Bag 'buscar_juego.php?q=mario'`).replace("$r=Get-Bag 'buscar_juego.php?q=halo&tienda=Xbox'",`$prefs.region='MX'
Invoke-RestMethod -Uri 'http://localhost/BAG/php/guardar_preferencias.php' -Method Post -WebSession $bagSession -Headers $headers -ContentType 'application/json' -Body ($prefs|ConvertTo-Json) | Out-Null
$r=Get-Bag 'buscar_juego.php?q=halo&tienda=Xbox'`).replace("$r=Get-Bag 'recomendaciones.php?",`$prefs.region='ES'
Invoke-RestMethod -Uri 'http://localhost/BAG/php/guardar_preferencias.php' -Method Post -WebSession $bagSession -Headers $headers -ContentType 'application/json' -Body ($prefs|ConvertTo-Json) | Out-Null
$r=Get-Bag 'recomendaciones.php?`));
