$ErrorActionPreference='Stop'
$bagSession=New-Object Microsoft.PowerShell.Commands.WebRequestSession
$bagTotal=0
function Check($condition,$label) {
    if(-not $condition){throw "FAIL: $label"}
    $script:bagTotal++; Write-Output "OK: $label"
}
function Get-Bag($route){Invoke-RestMethod -Uri ('http://localhost/BAG/php/'+$route) -WebSession $bagSession -TimeoutSec 65}
$monster=Get-Bag 'buscar_juego.php?q=monster%20hunter'
Check ($monster.games.Count -gt 0) 'Monster Hunter se descubre sin datos demo'
Check (@($monster.games | Where-Object fuente -eq 'DEMO').Count -eq 0) 'No hay sustitucion por catalogo demo'
Check (@($monster.games | Where-Object imagen -Like 'https://*').Count -gt 0) 'Portadas remotas de tienda'
$ark=Get-Bag 'buscar_juego.php?q=ark'
Check (@($ark.games | Where-Object nombre -Like 'ARK:*').Count -gt 0) 'ARK encuentra sus propios titulos'
Check (@($ark.games | Where-Object nombre -Like '*Dark Souls*').Count -eq 0) 'ARK no coincide con Dark Souls'
$prices=Get-Bag 'comparar_precios.php?q=cyberpunk%202077'
$base=$prices.prices | Where-Object juego -eq 'Cyberpunk 2077'
Check ($base.filas.Count -ge 2) 'Mismo titulo consultado en dos tiendas'
Check (@($base.filas | Where-Object {$null -ne $_.precio -and $_.moneda}).Count -ge 2) 'Importes y monedas publicados'
Check (@($base.filas | Where-Object {$_.url -match '/app/\d+|/game/'}).Count -ge 2) 'Enlaces a productos, no busquedas'
Check ($null -eq $base.mejor_precio) 'No compara directamente monedas distintas'
$detail=Get-Bag ('detalle_juego.php?id='+$monster.games[0].id)
Check ($detail.game.nombre -eq $monster.games[0].nombre) 'Detalle de Steam por identificador'
$gog=Get-Bag 'detalle_juego.php?id=gog-2093619782&q=Cyberpunk%202077'
Check ($gog.game.fuente -eq 'GOG') 'Detalle de GOG recuperado de la tienda'
$missing=Get-Bag 'buscar_juego.php?q=baginexistente7823749823'
Check ($missing.games.Count -eq 0) 'Titulo inexistente sin respuestas inventadas'
$console=Get-Bag 'buscar_juego.php?q=ark&plataforma=Xbox'
Check (@($console.games | Where-Object {$_.plataformas -contains 'Xbox'}).Count -gt 0) 'Xbox devuelve juegos actuales de consola'
$empty=Get-Bag 'buscar_juego.php?q='
Check ($empty.games.Count -eq 0) 'Inicio sin precargar juegos escritos a mano'
Write-Output "$bagTotal comprobaciones correctas. Datos obtenidos en vivo; sin llamadas a OpenAI."
