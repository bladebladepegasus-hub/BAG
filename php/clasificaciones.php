<?php
function clasificacion_valida($valor): string {
    if (!is_string($valor)||!in_array($valor,['A','B','B15','C','D'],true)) throw new InvalidArgumentException('Selecciona una clasificación válida: A, B, B15, C o D.');
    return $valor;
}
function categoria_contenido(array $j): ?string {
    $original=strtoupper(trim($j['clasificacion']??''));
    if (in_array($original,['A','B','B15','C','D'],true)) return $original;
    $rating=preg_replace('/^ESRB[ :]+/','',$original);
    $map=['E'=>'A','E10'=>'B','E10+'=>'B','T'=>'B15','T13+'=>'B15','M'=>'C','M17+'=>'C','AO'=>'D','AO18+'=>'D'];
    if(isset($map[$rating]))return $map[$rating];
    // PEGI y edades de tienda: agrupación orientativa de la app, no clasificación oficial mexicana.
    $edad=$j['edad_minima']??null;
    if ($edad===null)return null;
    if ($edad<=3)return 'A';
    if ($edad<=12)return 'B';
    if ($edad<=15)return 'B15';
    return 'C';
}
function admite_clasificacion(array $j,string $maxima): bool {
    $orden=['A'=>0,'B'=>1,'B15'=>2,'C'=>3,'D'=>4];$c=categoria_contenido($j);
    return $c!==null && $orden[$c]<=$orden[clasificacion_valida($maxima)];
}
function filtrar_clasificacion(array $juegos,string $maxima): array {
    return array_values(array_filter($juegos,fn($j)=>admite_clasificacion($j,$maxima)));
}
function advertir_clasificacion(array $juegos,string $maxima): array {
    clasificacion_valida($maxima);
    foreach($juegos as &$j){
        $j['categoria_filtro']=categoria_contenido($j);
        $j['advertencia']=$j['categoria_filtro']===null?'Clasificación no disponible en la fuente.':(admite_clasificacion($j,$maxima)?'':'Supera tu clasificación máxima seleccionada: '.$maxima.'.');
    }
    return $juegos;
}
