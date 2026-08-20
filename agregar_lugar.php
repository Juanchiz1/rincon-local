<?php
require 'includes/db.php';
require 'includes/funciones.php';
$bannerSitio = obtenerConfiguracion($pdo, 'banner_sitio', 'assets/banner_monteria_collage.jpg');

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $categoria = limpiar($_POST['categoria'] ?? '');
    $direccion = limpiar($_POST['direccion'] ?? '');
    $descripcion = limpiar($_POST['descripcion'] ?? '');
    $recomendaciones = limpiar($_POST['recomendaciones'] ?? '');
    $horarioApertura = trim($_POST['horario_apertura'] ?? '');
    $horarioCierre = trim($_POST['horario_cierre'] ?? '');
    $latitud = trim($_POST['latitud'] ?? '');
    $longitud = trim($_POST['longitud'] ?? '');
    $nombreImagen = null;

    if ($nombre === '') {
        $errores[] = 'El nombre es obligatorio.';
    }
    if ($categoria === '') {
        $errores[] = 'La categoría es obligatoria.';
    }
    if ($latitud !== '' && !is_numeric($latitud)) {
        $errores[] = 'La latitud debe ser un número.';
    }
    if ($longitud !== '' && !is_numeric($longitud)) {
        $errores[] = 'La longitud debe ser un número.';
    }

    // Foto de portada (la que sale en las tarjetas del listado).
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $tamanoMaximo = 5 * 1024 * 1024;

        if (!in_array($extension, $extensionesPermitidas)) {
            $errores[] = 'La imagen de portada debe ser JPG, PNG o WEBP.';
        } elseif ($_FILES['imagen']['size'] > $tamanoMaximo) {
            $errores[] = 'La imagen de portada no puede pesar más de 5MB.';
        } else {
            $nombreArchivo = uniqid('lugar_') . '.' . $extension;
            $rutaDestino = __DIR__ . '/uploads/' . $nombreArchivo;

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $errores[] = 'No se pudo guardar la imagen de portada, intenta de nuevo.';
            } else {
                $nombreImagen = 'uploads/' . $nombreArchivo;
            }
        }
    }

    // Galería: varias fotos adicionales, todas opcionales.
    $rutasGaleria = [];
    if (isset($_FILES['galeria']) && is_array($_FILES['galeria']['name'])) {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $tamanoMaximo = 5 * 1024 * 1024;
        $totalArchivos = count($_FILES['galeria']['name']);

        for ($i = 0; $i < $totalArchivos; $i++) {
            if ($_FILES['galeria']['error'][$i] !== UPLOAD_ERR_OK) {
                continue; // Campo vacío, se ignora sin generar error.
            }

            $extension = strtolower(pathinfo($_FILES['galeria']['name'][$i], PATHINFO_EXTENSION));
            if (!in_array($extension, $extensionesPermitidas)) {
                $errores[] = "Una de las fotos de la galería no es JPG, PNG o WEBP.";
                continue;
            }
            if ($_FILES['galeria']['size'][$i] > $tamanoMaximo) {
                $errores[] = "Una de las fotos de la galería pesa más de 5MB.";
                continue;
            }

            $nombreArchivo = uniqid('galeria_') . '.' . $extension;
            $rutaDestino = __DIR__ . '/uploads/' . $nombreArchivo;

            if (move_uploaded_file($_FILES['galeria']['tmp_name'][$i], $rutaDestino)) {
                $rutasGaleria[] = 'uploads/' . $nombreArchivo;
            }
        }
    }

    if (count($errores) === 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO lugares
                (nombre, categoria, direccion, descripcion, imagen, latitud, longitud, horario_apertura, horario_cierre, recomendaciones)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nombre,
            $categoria,
            $direccion,
            $descripcion !== '' ? $descripcion : null,
            $nombreImagen,
            $latitud !== '' ? $latitud : null,
            $longitud !== '' ? $longitud : null,
            $horarioApertura !== '' ? $horarioApertura : null,
            $horarioCierre !== '' ? $horarioCierre : null,
            $recomendaciones !== '' ? $recomendaciones : null,
        ]);

        $lugarId = $pdo->lastInsertId();

        if (count($rutasGaleria) > 0) {
            $stmtFoto = $pdo->prepare(
                "INSERT INTO lugares_fotos (lugar_id, ruta_imagen, orden) VALUES (?, ?, ?)"
            );
            foreach ($rutasGaleria as $orden => $ruta) {
                $stmtFoto->execute([$lugarId, $ruta, $orden]);
            }
        }

        $exito = true;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Agregar lugar - RincónLocal</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
</head>
<body>
        <header class="cabecera" style="--banner-url: url('/<?= limpiar($bannerSitio) ?>');">
        <h1>Agregar un nuevo lugar</h1>
        <a href="index.php" class="boton-agregar">← Volver al listado</a>
    </header>
    <div class="franja-vueltiada"></div>

    <main class="contenedor-formulario">
        <?php if ($exito): ?>
            <p class="mensaje-exito">Lugar agregado correctamente. <a href="index.php">Ver listado</a></p>
        <?php else: ?>
            <?php if (count($errores) > 0): ?>
                <ul class="mensaje-error">
                    <?php foreach ($errores as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="formulario-lugar">
                <label for="nombre">Nombre del lugar</label>
                <input type="text" id="nombre" name="nombre" value="<?= $_POST['nombre'] ?? '' ?>" required>

                <label for="categoria">Categoría</label>
                <input type="text" id="categoria" name="categoria" placeholder="Restaurante, Parque, Café..." value="<?= $_POST['categoria'] ?? '' ?>" required>

                <label for="direccion">Dirección</label>
                <input type="text" id="direccion" name="direccion" value="<?= $_POST['direccion'] ?? '' ?>">

                <label for="descripcion">Descripción</label>
                <textarea id="descripcion" name="descripcion" rows="3" placeholder="Cuéntale a la gente qué hace especial este lugar..."><?= $_POST['descripcion'] ?? '' ?></textarea>

                <label for="horario_apertura">Hora de apertura</label>
                <input type="time" id="horario_apertura" name="horario_apertura" value="<?= $_POST['horario_apertura'] ?? '' ?>">

                <label for="horario_cierre">Hora de cierre</label>
                <input type="time" id="horario_cierre" name="horario_cierre" value="<?= $_POST['horario_cierre'] ?? '' ?>">

                <label for="recomendaciones">Recomendaciones / actividades</label>
                <textarea id="recomendaciones" name="recomendaciones" rows="3" placeholder="Ej: Ve al atardecer, prueba el mote de queso, lleva efectivo..."><?= $_POST['recomendaciones'] ?? '' ?></textarea>

                <label for="imagen">Foto de portada (opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">

                <label for="galeria">Fotos adicionales (opcional, puedes elegir varias)</label>
                <input type="file" id="galeria" name="galeria[]" accept="image/*" multiple>

                <label>Ubicación en el mapa (opcional)</label>
                <p class="ayuda-mapa">Toca el botón para usar tu ubicación actual, o haz clic/toca directamente en el mapa para marcar el punto exacto.</p>

                <button type="button" id="btn-mi-ubicacion" class="boton-ubicacion-form">📍 Usar mi ubicación</button>
                <p id="mensaje-geo-form" class="mensaje-geo"></p>

                <div id="mapa-seleccionar" class="mapa-seleccionar"></div>

                <input type="hidden" id="latitud" name="latitud" value="<?= $_POST['latitud'] ?? '' ?>">
                <input type="hidden" id="longitud" name="longitud" value="<?= $_POST['longitud'] ?? '' ?>">

                <button type="submit">Guardar lugar</button>
            </form>
        <?php endif; ?>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Centro por defecto: Montería, para que el mapa no arranque en el medio del océano.
            const centroDefecto = [8.7500, -75.8800];
            const inputLat = document.getElementById('latitud');
            const inputLng = document.getElementById('longitud');
            const btnUbicacion = document.getElementById('btn-mi-ubicacion');
            const mensajeGeoForm = document.getElementById('mensaje-geo-form');

            const latInicial = parseFloat(inputLat.value);
            const lngInicial = parseFloat(inputLng.value);
            const hayUbicacionGuardada = !isNaN(latInicial) && !isNaN(lngInicial);

            const mapa = L.map('mapa-seleccionar').setView(
                hayUbicacionGuardada ? [latInicial, lngInicial] : centroDefecto,
                hayUbicacionGuardada ? 16 : 13
            );

            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '&copy; colaboradores de OpenStreetMap',
                maxZoom: 19,
            }).addTo(mapa);

            let marcador = null;

            function colocarMarcador(lat, lng) {
                if (marcador) {
                    marcador.setLatLng([lat, lng]);
                } else {
                    marcador = L.marker([lat, lng], { draggable: true }).addTo(mapa);
                    marcador.on('dragend', function () {
                        const posicion = marcador.getLatLng();
                        guardarCoordenadas(posicion.lat, posicion.lng);
                    });
                }
                guardarCoordenadas(lat, lng);
            }

            function guardarCoordenadas(lat, lng) {
                inputLat.value = lat.toFixed(6);
                inputLng.value = lng.toFixed(6);
            }

            if (hayUbicacionGuardada) {
                colocarMarcador(latInicial, lngInicial);
            }

            // Clic o toque en el mapa: mueve el marcador ahí.
            mapa.on('click', function (evento) {
                colocarMarcador(evento.latlng.lat, evento.latlng.lng);
            });

            // Botón "usar mi ubicación": centra el mapa y coloca el marcador con el GPS del navegador.
            if (btnUbicacion) {
                btnUbicacion.addEventListener('click', function () {
                    if (!navigator.geolocation) {
                        mensajeGeoForm.textContent = 'Tu navegador no soporta geolocalización, marca el punto directamente en el mapa.';
                        return;
                    }

                    btnUbicacion.disabled = true;
                    btnUbicacion.textContent = 'Buscando tu ubicación...';
                    mensajeGeoForm.textContent = '';

                    navigator.geolocation.getCurrentPosition(
                        function (posicion) {
                            const lat = posicion.coords.latitude;
                            const lng = posicion.coords.longitude;
                            mapa.setView([lat, lng], 17);
                            colocarMarcador(lat, lng);
                            btnUbicacion.disabled = false;
                            btnUbicacion.textContent = '📍 Usar mi ubicación';
                        },
                        function (error) {
                            btnUbicacion.disabled = false;
                            btnUbicacion.textContent = '📍 Usar mi ubicación';
                            mensajeGeoForm.textContent = error.code === error.PERMISSION_DENIED
                                ? 'Necesitas permitir el acceso a tu ubicación, o marca el punto directamente en el mapa.'
                                : 'No se pudo obtener tu ubicación, marca el punto directamente en el mapa.';
                        }
                    );
                });
            }
        });
    </script>
</body>
</html>