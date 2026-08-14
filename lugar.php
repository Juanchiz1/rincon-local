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

$stmtFotos = $pdo->prepare("SELECT * FROM lugares_fotos WHERE lugar_id = ? ORDER BY orden ASC");
$stmtFotos->execute([$id]);
$fotosGaleria = $stmtFotos->fetchAll();

$emojisPermitidos = ['😍', '😊', '😐', '😕', '😡'];

$stmtResenas = $pdo->prepare("SELECT * FROM resenas WHERE lugar_id = ? ORDER BY creado_en DESC");
$stmtResenas->execute([$lugar['id']]);
$resenas = $stmtResenas->fetchAll();
$promedio = promedioResenas($resenas);
$promediosCategorias = promediosPorCategoria($resenas);
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
    <header class="cabecera-lugar" <?php if ($lugar['imagen']): ?>style="background-image: url('<?= limpiar($lugar['imagen']) ?>');"<?php endif; ?>>
        <div class="overlay-cabecera-lugar">
            <a href="index.php" class="volver-lugar">← Volver al listado</a>
            <span class="categoria-badge"><?= limpiar($lugar['categoria']) ?></span>
            <h1><?= limpiar($lugar['nombre']) ?></h1>
            <p><?= limpiar($lugar['direccion']) ?></p>
            <p class="promedio-grande" id="promedio-grande" <?= count($resenas) === 0 ? 'style="display:none;"' : '' ?>>
                ⭐ <span id="valor-promedio-general"><?= $promedio ?></span> / 5
                <span>(<span id="conteo-resenas-cabecera"><?= count($resenas) ?></span> reseñas)</span>
            </p>
        </div>
    </header>
    <div class="franja-vueltiada"></div>

    <main class="detalle-lugar">
        <?php if ($lugar['descripcion']): ?>
        <section class="descripcion-lugar">
            <p><?= nl2br($lugar['descripcion']) ?></p>
        </section>
        <?php endif; ?>

        <?php if ($lugar['horario_apertura'] && $lugar['horario_cierre']): ?>
        <section class="horario-lugar">
            🕒 <?= date('g:i A', strtotime($lugar['horario_apertura'])) ?> — <?= date('g:i A', strtotime($lugar['horario_cierre'])) ?>
        </section>
        <?php endif; ?>

        <?php if (count($fotosGaleria) > 0): ?>
        <section class="galeria-lugar">
            <?php foreach ($fotosGaleria as $foto): ?>
                <img src="<?= limpiar($foto['ruta_imagen']) ?>" alt="<?= limpiar($lugar['nombre']) ?>" class="foto-clickeable">
            <?php endforeach; ?>
        </section>
        <?php endif; ?>

        <?php if ($lugar['latitud'] && $lugar['longitud']): ?>
        <section class="mapa-lugar">
            <iframe
                width="100%"
                height="280"
                style="border:0; border-radius: 12px;"
                loading="lazy"
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q=<?= $lugar['latitud'] ?>,<?= $lugar['longitud'] ?>&z=17&output=embed">
            </iframe>
        </section>
        <?php endif; ?>

        <section class="resumen-calificaciones" id="resumen-calificaciones" <?= count($resenas) === 0 ? 'style="display:none;"' : '' ?>>
            <h2>Calificaciones</h2>

            <div class="barra-categoria">
                <span class="etiqueta-barra">Comida</span>
                <div class="barra-fondo"><div class="barra-relleno" id="barra-comida" style="width: <?= ($promediosCategorias['comida'] / 5) * 100 ?>%"></div></div>
                <span class="valor-barra" id="valor-comida"><?= $promediosCategorias['comida'] ?></span>
            </div>

            <div class="barra-categoria">
                <span class="etiqueta-barra">Servicio</span>
                <div class="barra-fondo"><div class="barra-relleno" id="barra-servicio" style="width: <?= ($promediosCategorias['servicio'] / 5) * 100 ?>%"></div></div>
                <span class="valor-barra" id="valor-servicio"><?= $promediosCategorias['servicio'] ?></span>
            </div>

            <div class="barra-categoria">
                <span class="etiqueta-barra">Ambiente</span>
                <div class="barra-fondo"><div class="barra-relleno" id="barra-ambiente" style="width: <?= ($promediosCategorias['ambiente'] / 5) * 100 ?>%"></div></div>
                <span class="valor-barra" id="valor-ambiente"><?= $promediosCategorias['ambiente'] ?></span>
            </div>
        </section>

        <section class="formulario-resena">
            <h2>Deja tu reseña</h2>

            <div id="mensaje-resena"></div>

            <form id="formulario-resena" enctype="multipart/form-data" class="formulario-lugar">
                <input type="hidden" name="lugar_id" value="<?= $lugar['id'] ?>">

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

                <label for="imagen_resena">Agrega una foto (opcional)</label>
                <input type="file" id="imagen_resena" name="imagen" accept="image/*">

                <button type="submit">Publicar reseña</button>
            </form>
        </section>

        <section class="lista-resenas">
            <h2>Reseñas (<span id="conteo-resenas"><?= count($resenas) ?></span>)</h2>

            <div id="contenedor-resenas">
                <?php if (count($resenas) === 0): ?>
                    <p id="mensaje-sin-resenas">Sé el primero en reseñar este lugar.</p>
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
                            <?php if ($resena['imagen']): ?>
                                <img src="<?= limpiar($resena['imagen']) ?>" alt="Foto de la reseña" class="foto-resena foto-clickeable">
                            <?php endif; ?>
                            <?php if ($resena['comentario']): ?>
                                <p class="comentario-resena"><?= $resena['comentario'] ?></p>
                            <?php endif; ?>
                            <time><?= date('d/m/Y', strtotime($resena['creado_en'])) ?></time>
                        </article>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </section>

        <?php if ($lugar['recomendaciones']): ?>
        <section class="recomendaciones-lugar">
            <h2>Recomendaciones</h2>
            <p><?= nl2br($lugar['recomendaciones']) ?></p>
        </section>
        <?php endif; ?>
    </main>

    <div id="lightbox" class="lightbox oculto">
        <span class="cerrar-lightbox">&times;</span>
        <img id="lightbox-img" src="" alt="Foto ampliada">
    </div>

    <script src="js/app.js"></script>
</body>
</html>