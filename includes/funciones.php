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

function promediosPorCategoria(array $resenas)
{
    if (count($resenas) === 0) {
        return ['comida' => 0, 'servicio' => 0, 'ambiente' => 0];
    }

    $sumaComida = 0;
    $sumaServicio = 0;
    $sumaAmbiente = 0;

    foreach ($resenas as $r) {
        $sumaComida += $r['rating_comida'];
        $sumaServicio += $r['rating_servicio'];
        $sumaAmbiente += $r['rating_ambiente'];
    }

    $total = count($resenas);

    return [
        'comida' => round($sumaComida / $total, 1),
        'servicio' => round($sumaServicio / $total, 1),
        'ambiente' => round($sumaAmbiente / $total, 1),
    ];
}