<?php
require 'includes/db.php';
require 'includes/funciones.php';

$stmt = $pdo->query("SELECT * FROM lugares ORDER BY nombre ASC");
$lugares = $stmt->fetchAll();

$stmtResenas = $pdo->prepare("SELECT * FROM resenas WHERE lugar_id = ?");
foreach ($lugares as &$lugar) {
    $stmtResenas->execute([$lugar['id']]);
    $resenasDelLugar = $stmtResenas->fetchAll();
    $lugar['promedio'] = promedioResenas($resenasDelLugar);
    $lugar['total_resenas'] = count($resenasDelLugar);
}
unset($lugar);

// Si llegamos con lat/lng en la URL, ordenamos por cercanía real.
$ordenandoPorCercania = isset($_GET['lat'], $_GET['lng'])
    && is_numeric($_GET['lat'])
    && is_numeric($_GET['lng']);

if ($ordenandoPorCercania) {
    $latUsuario = (float) $_GET['lat'];
    $lngUsuario = (float) $_GET['lng'];

    foreach ($lugares as &$lugar) {
        if ($lugar['latitud'] !== null && $lugar['longitud'] !== null) {
            $lugar['distancia'] = distanciaKm($latUsuario, $lngUsuario, $lugar['latitud'], $lugar['longitud']);
        } else {
            $lugar['distancia'] = null;
        }
    }
    unset($lugar);

    // Los que tienen distancia van primero (más cerca primero); los que
    // no tienen coordenadas quedan al final, sin importar el orden entre ellos.
    usort($lugares, function ($a, $b) {
        if ($a['distancia'] === null && $b['distancia'] === null) return 0;
        if ($a['distancia'] === null) return 1;
        if ($b['distancia'] === null) return -1;
        return $a['distancia'] <=> $b['distancia'];
    });
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>RincónLocal - Lugares de Montería</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="cabecera">
        <h1>RincónLocal</h1>
        <p>Descubre y reseña los mejores rincones de Montería</p>
        <div class="botones-cabecera">
            <a href="agregar_lugar.php" class="boton-agregar">+ Agregar lugar</a>
            <button id="btn-cerca-mi" class="boton-agregar boton-secundario" type="button">📍 Lugares cerca de mí</button>
        </div>
        <p id="mensaje-geo" class="mensaje-geo"></p>
    </header>
    <div class="franja-vueltiada"></div>

    <main class="contenedor-lugares">
        <div class="barra-busqueda">
            <input type="text" id="buscador-lugares" placeholder="🔍 Buscar por nombre o categoría..." autocomplete="off">
        </div>

        <?php if ($ordenandoPorCercania): ?>

        <?php if (count($lugares) === 0): ?>
            <p>Todavía no hay lugares registrados.</p>
        <?php else: ?>
            <?php foreach ($lugares as $lugar): ?>
                <a href="lugar.php?id=<?= $lugar['id'] ?>" class="tarjeta-lugar" data-nombre="<?= strtolower(limpiar($lugar['nombre'])) ?>" data-categoria="<?= strtolower(limpiar($lugar['categoria'])) ?>">
                    <div class="imagen-lugar <?= $lugar['imagen'] ? '' : 'sin-imagen' ?>"
                         <?php if ($lugar['imagen']): ?>
                            style="background-image: url('<?= limpiar($lugar['imagen']) ?>');"
                         <?php endif; ?>>
                        <span class="categoria-badge"><?= limpiar($lugar['categoria']) ?></span>
                        <?php if ($ordenandoPorCercania && $lugar['distancia'] !== null): ?>
                            <span class="distancia-badge"><?= $lugar['distancia'] ?> km</span>
                        <?php endif; ?>
                    </div>
                    <div class="info-lugar">
                        <h2><?= limpiar($lugar['nombre']) ?></h2>
                        <p class="direccion"><?= limpiar($lugar['direccion']) ?></p>
                        <div class="rating">
                            <?php if ($lugar['total_resenas'] > 0): ?>
                                ⭐ <?= $lugar['promedio'] ?> / 5
                                <span class="total">(<?= $lugar['total_resenas'] ?> reseñas)</span>
                            <?php else: ?>
                                <span class="sin-resenas">Sin reseñas todavía</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
            <p id="mensaje-sin-resultados" class="mensaje-sin-resultados oculto">No encontramos lugares que coincidan con tu búsqueda.</p>
        <?php endif; ?>
        <?php endif; ?>
    </main>
    <script src="js/app.js"></script>
</body>
</html>