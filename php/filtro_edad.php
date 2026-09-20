<?php
function edad_valida($edad): ?int {
    if ($edad === '' || $edad === null) return null;
    if (filter_var($edad, FILTER_VALIDATE_INT) === false || (int)$edad < 7 || (int)$edad > 120) throw new InvalidArgumentException('La edad debe ser un número entero entre 7 y 120.');
    return (int)$edad;
}
function filtro_edad(array $juegos, ?int $edad): array {
    // Sin edad declarada, no se recomiendan títulos de clasificación desconocida o mayores de 7.
    return array_values(array_filter($juegos, fn($j)=>$j['edad_minima'] !== null && $j['edad_minima'] <= ($edad ?? 7)));
}
function advertir_edad(array $juegos, ?int $edad): array {
    foreach ($juegos as &$j) $j['advertencia'] = $j['edad_minima'] === null ? 'Clasificación no disponible. Revisa el contenido con un adulto.' : ($j['edad_minima'] > ($edad ?? 7) ? '⚠️ Este videojuego puede contener contenido no recomendado para tu edad. Revisa su clasificación.' : '');
    return $juegos;
}
