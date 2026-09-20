$ErrorActionPreference='Stop'
$bagSession=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$bagTotal=0
function Check($condition,$label){if(-not $condition){throw "FAIL: $label"};$script:bagTotal++;Write-Output "OK: $label"}
function Get-Bag($route){Invoke-RestMethod -Uri ('http://localhost/BAG/php/'+$route) -WebSession $bagSession -TimeoutSec 90}
$profile=Get-Bag 'preferencias.php'
$headers=@{'X-CSRF-Token'=$profile.csrf}
$prefs=@{region='ES';idioma='es';tema='oscuro';clasificacion_maxima='B';plataforma='';genero='';modo_juego='Ambos'}
Invoke-RestMethod -Uri 'http://localhost/BAG/php/guardar_preferencias.php' -Method Post -WebSession $bagSession -Headers $headers -ContentType 'application/json' -Body ($prefs|ConvertTo-Json) | Out-Null
$r=Get-Bag 'buscar_juego.php?q=mario'
Check ($r.sources.Count -eq 5) 'Se consultan cinco fuentes'
Check (@($r.games|Where-Object fuente -eq Nintendo).Count -gt 0) 'Nintendo devuelve juegos'
Check (@($r.games|Where-Object fuente -eq Eneba).Count -gt 0) 'Eneba devuelve productos'
$n=$r.games|Where-Object fuente -eq Nintendo|Select-Object -First 1
Check ($n.region -eq 'España' -and $n.moneda -eq 'EUR' -and $n.precio_desde) 'Nintendo marca región y mínimo de variantes'
$e=$r.games|Where-Object fuente -eq Eneba|Select-Object -First 1
Check ($e.region -and $e.plataformas.Count -gt 0 -and $e.precio -gt 0) 'Eneba informa región, plataforma e importe'
$det=Get-Bag ('detalle_juego.php?id='+$e.id+'&q='+[Uri]::EscapeDataString($e.nombre))
Check ($det.game.id -eq $e.id) 'Detalle Eneba mantiene identidad del producto'
$prefs.region='MX'
Invoke-RestMethod -Uri 'http://localhost/BAG/php/guardar_preferencias.php' -Method Post -WebSession $bagSession -Headers $headers -ContentType 'application/json' -Body ($prefs|ConvertTo-Json) | Out-Null
$r=Get-Bag 'buscar_juego.php?q=halo&tienda=Xbox'
Check (@($r.games|Where-Object fuente -ne Xbox).Count -eq 0 -and $r.games.Count -gt 0) 'Selector de tienda Xbox'
$x=$r.games|Select-Object -First 1
Check ($x.region -eq 'México' -and $x.moneda -eq 'MXN') 'Xbox consulta mercado mexicano'
$det=Get-Bag ('detalle_juego.php?id='+$x.id+'&q='+[Uri]::EscapeDataString($x.nombre))
Check ($det.game.id -eq $x.id) 'Detalle Xbox conserva SKU'
$prefs.region='ES'
Invoke-RestMethod -Uri 'http://localhost/BAG/php/guardar_preferencias.php' -Method Post -WebSession $bagSession -Headers $headers -ContentType 'application/json' -Body ($prefs|ConvertTo-Json) | Out-Null
$r=Get-Bag 'recomendaciones.php?clasificacion_maxima=B&plataforma=Nintendo%20Switch&modo_juego=Ambos'
Check ($r.games.Count -gt 0) 'Recomendaciones Nintendo en línea'
Check (@($r.games|Where-Object {$_.categoria_filtro -notin @('A','B') -or $_.fuente -eq 'DEMO'}).Count -eq 0) 'Recomendaciones respetan clasificación sin demo'
Check (@($r.games|Where-Object {-not $_.motivo}).Count -eq 0) 'Cada recomendación explica su motivo'
$r=Get-Bag 'buscar_juego.php?q=mario&plataforma=Nintendo%20Switch%202'
Check ($r.games.Count -gt 0 -and @($r.games|Where-Object {$_.plataformas -notcontains 'Nintendo Switch 2'}).Count -eq 0) 'Filtro Nintendo Switch 2'
$r=Get-Bag 'comparar_precios.php?q=elden%20ring&tienda=Eneba'
Check ($r.prices.Count -gt 0 -and $r.prices[0].filas[0].precio_desde -and $r.prices[0].filas[0].condiciones) 'Ofertas marketplace con precio desde y condiciones'
Write-Output "$bagTotal comprobaciones multitienda correctas."
