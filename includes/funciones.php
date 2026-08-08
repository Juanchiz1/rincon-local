<?php
// Funciones que se repiten en varias páginas.

function limpiar($valor)
{
    return htmlspecialchars(trim($valor), ENT_QUOTES, 'UTF-8');
}

function promedioResenas(array $resenas)
{
    if (count($resenas) === 0) {
        return 0;
    }

    $suma = 0;
    foreach ($resenas as $r) {
        $suma += ($r['rating_comida'] + $r['rating_servicio'] + $r['rating_ambiente']) / 3;
    }

    return round($suma / count($resenas), 1);
}

function distanciaKm($lat1, $lon1, $lat2, $lon2)
{
    // Fórmula de Haversine: distancia entre dos coordenadas en km.
    $radioTierra = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2)
        + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
        * sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return round($radioTierra * $c, 2);
}
