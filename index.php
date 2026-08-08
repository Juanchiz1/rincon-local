<?php
require 'includes/db.php';
require 'includes/funciones.php';

$stmt = $pdo->query("SELECT * FROM lugares ORDER BY nombre ASC");
$lugares = $stmt->fetchAll();

// Para cada lugar, traemos sus reseñas y calculamos el promedio.
$stmtResenas = $pdo->prepare("SELECT * FROM resenas WHERE lugar_id = ?");
foreach ($lugares as &$lugar) {
    $stmtResenas->execute([$lugar['id']]);
    $resenasDelLugar = $stmtResenas->fetchAll();
    $lugar['promedio'] = promedioResenas($resenasDelLugar);
    $lugar['total_resenas'] = count($resenasDelLugar);
}
unset($lugar);
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
        <a href="agregar_lugar.php" class="boton-agregar">+ Agregar lugar</a>
    </header>

    <main class="contenedor-lugares">
        <?php if (count($lugares) === 0): ?>
            <p>Todavía no hay lugares registrados.</p>
        <?php else: ?>
            <?php foreach ($lugares as $lugar): ?>
                <a href="lugar.php?id=<?= $lugar['id'] ?>" class="tarjeta-lugar">
                    <span class="categoria"><?= limpiar($lugar['categoria']) ?></span>
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
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>