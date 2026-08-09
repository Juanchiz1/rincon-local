<?php
require 'includes/db.php';
require 'includes/funciones.php';

$id = $_GET['id'] ?? null;

if (!$id || !is_numeric($id)) {
    die('Lugar no encontrado.');
}

$stmt = $pdo->prepare("SELECT * FROM lugares WHERE id = ?");
$stmt->execute([$id]);
$lugar = $stmt->fetch();

if (!$lugar) {
    die('Lugar no encontrado.');
}

$errores = [];
$exito = false;

// Emojis permitidos, para no guardar cualquier texto en esa columna.
$emojisPermitidos = ['😍', '😊', '😐', '😕', '😡'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $autor = limpiar($_POST['autor'] ?? '');
    $comentario = limpiar($_POST['comentario'] ?? '');
    $emoji = $_POST['emoji'] ?? '';
    $ratingComida = $_POST['rating_comida'] ?? '';
    $ratingServicio = $_POST['rating_servicio'] ?? '';
    $ratingAmbiente = $_POST['rating_ambiente'] ?? '';

    if ($autor === '') {
        $errores[] = 'Tu nombre es obligatorio.';
    }
    foreach (['rating_comida' => $ratingComida, 'rating_servicio' => $ratingServicio, 'rating_ambiente' => $ratingAmbiente] as $campo => $valor) {
        if ($valor === '' || !in_array($valor, ['1', '2', '3', '4', '5'])) {
            $errores[] = 'Debes calificar todas las categorías con estrellas.';
            break;
        }
    }
    if ($emoji !== '' && !in_array($emoji, $emojisPermitidos)) {
        $emoji = '';
    }

    if (count($errores) === 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO resenas (lugar_id, autor, comentario, emoji, rating_comida, rating_servicio, rating_ambiente)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $lugar['id'],
            $autor,
            $comentario,
            $emoji !== '' ? $emoji : null,
            $ratingComida,
            $ratingServicio,
            $ratingAmbiente,
        ]);

        // Redirigimos para evitar que al recargar la página se reenvíe el formulario.
        header("Location: lugar.php?id={$lugar['id']}&exito=1");
        exit;
    }
}

$exito = isset($_GET['exito']);

$stmtResenas = $pdo->prepare("SELECT * FROM resenas WHERE lugar_id = ? ORDER BY creado_en DESC");
$stmtResenas->execute([$lugar['id']]);
$resenas = $stmtResenas->fetchAll();
$promedio = promedioResenas($resenas);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= limpiar($lugar['nombre']) ?> - RincónLocal</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header class="cabecera-lugar" <?php if ($lugar['imagen']): ?>style="background-image: url('uploads/<?= limpiar($lugar['imagen']) ?>');"<?php endif; ?>>
        <div class="overlay-cabecera-lugar">
            <a href="index.php" class="volver-lugar">← Volver al listado</a>
            <span class="categoria-badge"><?= limpiar($lugar['categoria']) ?></span>
            <h1><?= limpiar($lugar['nombre']) ?></h1>
            <p><?= limpiar($lugar['direccion']) ?></p>
            <?php if (count($resenas) > 0): ?>
                <p class="promedio-grande">⭐ <?= $promedio ?> / 5 <span>(<?= count($resenas) ?> reseñas)</span></p>
            <?php endif; ?>
        </div>
    </header>
    <div class="franja-vueltiada"></div>

    <main class="detalle-lugar">
        <?php if ($lugar['latitud'] && $lugar['longitud']): ?>
        <section class="mapa-lugar">
            <iframe
                width="100%"
                height="280"
                style="border:0; border-radius: 12px;"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?= $lugar['latitud'] ?>,<?= $lugar['longitud'] ?>&output=embed">
            </iframe>
        </section>
        <?php endif; ?>

        <section class="formulario-resena">
            <h2>Deja tu reseña</h2>

            <?php if ($exito): ?>
                <p class="mensaje-exito">¡Gracias por tu reseña!</p>
            <?php endif; ?>

            <?php if (count($errores) > 0): ?>
                <ul class="mensaje-error">
                    <?php foreach ($errores as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" class="formulario-lugar">
                <label for="autor">Tu nombre</label>
                <input type="text" id="autor" name="autor" required>

                <label>Comida / producto</label>
                <div class="estrellas-input">
                    <input type="radio" id="comida5" name="rating_comida" value="5"><label for="comida5">★</label>
                    <input type="radio" id="comida4" name="rating_comida" value="4"><label for="comida4">★</label>
                    <input type="radio" id="comida3" name="rating_comida" value="3"><label for="comida3">★</label>
                    <input type="radio" id="comida2" name="rating_comida" value="2"><label for="comida2">★</label>
                    <input type="radio" id="comida1" name="rating_comida" value="1"><label for="comida1">★</label>
                </div>

                <label>Servicio</label>
                <div class="estrellas-input">
                    <input type="radio" id="servicio5" name="rating_servicio" value="5"><label for="servicio5">★</label>
                    <input type="radio" id="servicio4" name="rating_servicio" value="4"><label for="servicio4">★</label>
                    <input type="radio" id="servicio3" name="rating_servicio" value="3"><label for="servicio3">★</label>
                    <input type="radio" id="servicio2" name="rating_servicio" value="2"><label for="servicio2">★</label>
                    <input type="radio" id="servicio1" name="rating_servicio" value="1"><label for="servicio1">★</label>
                </div>

                <label>Ambiente</label>
                <div class="estrellas-input">
                    <input type="radio" id="ambiente5" name="rating_ambiente" value="5"><label for="ambiente5">★</label>
                    <input type="radio" id="ambiente4" name="rating_ambiente" value="4"><label for="ambiente4">★</label>
                    <input type="radio" id="ambiente3" name="rating_ambiente" value="3"><label for="ambiente3">★</label>
                    <input type="radio" id="ambiente2" name="rating_ambiente" value="2"><label for="ambiente2">★</label>
                    <input type="radio" id="ambiente1" name="rating_ambiente" value="1"><label for="ambiente1">★</label>
                </div>

                <label>¿Cómo te sentiste? (opcional)</label>
                <div class="emojis-input">
                    <?php foreach ($emojisPermitidos as $i => $e): ?>
                        <input type="radio" id="emoji<?= $i ?>" name="emoji" value="<?= $e ?>">
                        <label for="emoji<?= $i ?>"><?= $e ?></label>
                    <?php endforeach; ?>
                </div>

                <label for="comentario">Comentario (opcional)</label>
                <textarea id="comentario" name="comentario" rows="3"></textarea>

                <button type="submit">Publicar reseña</button>
            </form>
        </section>

        <section class="lista-resenas">
            <h2>Reseñas (<?= count($resenas) ?>)</h2>

            <?php if (count($resenas) === 0): ?>
                <p>Sé el primero en reseñar este lugar.</p>
            <?php else: ?>
                <?php foreach ($resenas as $resena): ?>
                    <article class="tarjeta-resena">
                        <div class="cabecera-resena">
                            <strong><?= limpiar($resena['autor']) ?></strong>
                            <?php if ($resena['emoji']): ?>
                                <span class="emoji-resena"><?= $resena['emoji'] ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="detalle-ratings">
                            <span>Comida: <?= str_repeat('★', $resena['rating_comida']) . str_repeat('☆', 5 - $resena['rating_comida']) ?></span>
                            <span>Servicio: <?= str_repeat('★', $resena['rating_servicio']) . str_repeat('☆', 5 - $resena['rating_servicio']) ?></span>
                            <span>Ambiente: <?= str_repeat('★', $resena['rating_ambiente']) . str_repeat('☆', 5 - $resena['rating_ambiente']) ?></span>
                        </div>
                        <?php if ($resena['comentario']): ?>
                            <p class="comentario-resena"><?= $resena['comentario'] ?></p>
                        <?php endif; ?>
                        <time><?= date('d/m/Y', strtotime($resena['creado_en'])) ?></time>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>
</body>
</html>