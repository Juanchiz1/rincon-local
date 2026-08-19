<?php
require '../includes/db.php';
require '../includes/funciones.php';
require '../includes/auth.php';
requerirLogin();

$id = $_GET['id'] ?? $_POST['id'] ?? null;
$esEdicion = $id && is_numeric($id);
$lugar = [
    'nombre' => '', 'categoria' => '', 'direccion' => '', 'descripcion' => '',
    'imagen' => null, 'latitud' => '', 'longitud' => '',
    'horario_apertura' => '', 'horario_cierre' => '', 'recomendaciones' => '',
];

if ($esEdicion) {
    $stmt = $pdo->prepare("SELECT * FROM lugares WHERE id = ?");
    $stmt->execute([$id]);
    $encontrado = $stmt->fetch();
    if (!$encontrado) {
        die('Lugar no encontrado.');
    }
    $lugar = $encontrado;
}

$fotosGaleria = [];
if ($esEdicion) {
    $stmtFotos = $pdo->prepare("SELECT * FROM lugares_fotos WHERE lugar_id = ? ORDER BY orden ASC");
    $stmtFotos->execute([$id]);
    $fotosGaleria = $stmtFotos->fetchAll();
}

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
    $rutaImagen = $lugar['imagen']; // Por defecto conserva la que ya tenía.

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

    // Si suben una nueva foto de portada, reemplaza a la anterior.
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $tamanoMaximo = 5 * 1024 * 1024;

        if (!in_array($extension, $extensionesPermitidas)) {
            $errores[] = 'La imagen debe ser JPG, PNG o WEBP.';
        } elseif ($_FILES['imagen']['size'] > $tamanoMaximo) {
            $errores[] = 'La imagen no puede pesar más de 5MB.';
        } else {
            $nombreArchivo = uniqid('lugar_') . '.' . $extension;
            $rutaDestino = __DIR__ . '/../uploads/' . $nombreArchivo;

            if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $rutaImagen = 'uploads/' . $nombreArchivo;
            } else {
                $errores[] = 'No se pudo guardar la nueva imagen.';
            }
        }
    }

        if (count($errores) === 0) {
        if ($esEdicion) {
            $stmt = $pdo->prepare(
                "UPDATE lugares SET
                    nombre = ?, categoria = ?, direccion = ?, descripcion = ?, imagen = ?,
                    latitud = ?, longitud = ?, horario_apertura = ?, horario_cierre = ?, recomendaciones = ?
                 WHERE id = ?"
            );
            $stmt->execute([
                $nombre, $categoria, $direccion, $descripcion !== '' ? $descripcion : null, $rutaImagen,
                $latitud !== '' ? $latitud : null, $longitud !== '' ? $longitud : null,
                $horarioApertura !== '' ? $horarioApertura : null, $horarioCierre !== '' ? $horarioCierre : null,
                $recomendaciones !== '' ? $recomendaciones : null,
                $id,
            ]);
            $lugarId = $id;
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO lugares
                    (nombre, categoria, direccion, descripcion, imagen, latitud, longitud, horario_apertura, horario_cierre, recomendaciones)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->execute([
                $nombre, $categoria, $direccion, $descripcion !== '' ? $descripcion : null, $rutaImagen,
                $latitud !== '' ? $latitud : null, $longitud !== '' ? $longitud : null,
                $horarioApertura !== '' ? $horarioApertura : null, $horarioCierre !== '' ? $horarioCierre : null,
                $recomendaciones !== '' ? $recomendaciones : null,
            ]);
            $lugarId = $pdo->lastInsertId();
        }

        // Elimina las fotos de galería que el admin marcó para borrar.
        if (!empty($_POST['eliminar_foto']) && is_array($_POST['eliminar_foto'])) {
            $stmtEliminarFoto = $pdo->prepare("DELETE FROM lugares_fotos WHERE id = ? AND lugar_id = ?");
            foreach ($_POST['eliminar_foto'] as $fotoId) {
                if (is_numeric($fotoId)) {
                    $stmtEliminarFoto->execute([$fotoId, $lugarId]);
                }
            }
        }

        // Sube las fotos nuevas de galería, si se seleccionó alguna.
        if (isset($_FILES['galeria']) && is_array($_FILES['galeria']['name'])) {
            $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
            $tamanoMaximo = 5 * 1024 * 1024;
            $stmtOrden = $pdo->prepare("SELECT COALESCE(MAX(orden), -1) FROM lugares_fotos WHERE lugar_id = ?");
            $stmtOrden->execute([$lugarId]);
            $siguienteOrden = $stmtOrden->fetchColumn() + 1;

            $stmtFoto = $pdo->prepare("INSERT INTO lugares_fotos (lugar_id, ruta_imagen, orden) VALUES (?, ?, ?)");

            foreach ($_FILES['galeria']['name'] as $i => $nombreOriginal) {
                if ($_FILES['galeria']['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }

                $extension = strtolower(pathinfo($nombreOriginal, PATHINFO_EXTENSION));
                if (!in_array($extension, $extensionesPermitidas)) {
                    continue;
                }
                if ($_FILES['galeria']['size'][$i] > $tamanoMaximo) {
                    continue;
                }

                $nombreArchivo = uniqid('galeria_') . '.' . $extension;
                $rutaDestino = __DIR__ . '/../uploads/' . $nombreArchivo;

                if (move_uploaded_file($_FILES['galeria']['tmp_name'][$i], $rutaDestino)) {
                    $stmtFoto->execute([$lugarId, 'uploads/' . $nombreArchivo, $siguienteOrden]);
                    $siguienteOrden++;
                }
            }
        }

        header('Location: lugares.php');
        exit;
    }

    // Si hubo error, mantenemos lo que el usuario escribió en pantalla.
    $lugar = array_merge($lugar, compact(
        'nombre', 'categoria', 'direccion', 'descripcion', 'latitud', 'longitud',
        'horarioApertura', 'horarioCierre', 'recomendaciones'
    ));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $esEdicion ? 'Editar' : 'Agregar' ?> lugar - Panel de administración</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="cabecera-admin">
        <h1><?= $esEdicion ? 'Editar' : 'Agregar' ?> lugar</h1>
        <p><a href="lugares.php" class="link-logout">← Volver al listado</a></p>
    </header>

    <main class="contenedor-formulario">
        <?php if (count($errores) > 0): ?>
            <ul class="mensaje-error">
                <?php foreach ($errores as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="formulario-lugar">
            <?php if ($esEdicion): ?>
                <input type="hidden" name="id" value="<?= $id ?>">
            <?php endif; ?>

            <label for="nombre">Nombre del lugar</label>
            <input type="text" id="nombre" name="nombre" value="<?= limpiar($lugar['nombre']) ?>" required>

            <label for="categoria">Categoría</label>
            <input type="text" id="categoria" name="categoria" value="<?= limpiar($lugar['categoria']) ?>" required>

            <label for="direccion">Dirección</label>
            <input type="text" id="direccion" name="direccion" value="<?= limpiar($lugar['direccion'] ?? '') ?>">

            <label for="descripcion">Descripción</label>
            <textarea id="descripcion" name="descripcion" rows="3"><?= limpiar($lugar['descripcion'] ?? '') ?></textarea>

            <label for="horario_apertura">Hora de apertura</label>
            <input type="time" id="horario_apertura" name="horario_apertura" value="<?= $lugar['horario_apertura'] ?? '' ?>">

            <label for="horario_cierre">Hora de cierre</label>
            <input type="time" id="horario_cierre" name="horario_cierre" value="<?= $lugar['horario_cierre'] ?? '' ?>">

            <label for="recomendaciones">Recomendaciones / actividades</label>
            <textarea id="recomendaciones" name="recomendaciones" rows="3"><?= limpiar($lugar['recomendaciones'] ?? '') ?></textarea>

            <label for="imagen">Foto de portada <?= $esEdicion ? '(deja vacío para conservar la actual)' : '(opcional)' ?></label>
            <?php if ($esEdicion && $lugar['imagen']): ?>
                <img src="../<?= limpiar($lugar['imagen']) ?>" alt="Portada actual" class="miniatura-actual">
            <?php endif; ?>
            <input type="file" id="imagen" name="imagen" accept="image/*">
                        <?php if ($esEdicion && count($fotosGaleria) > 0): ?>
            <label>Fotos de la galería actual (marca las que quieras eliminar)</label>
            <div class="galeria-admin-editar">
                <?php foreach ($fotosGaleria as $foto): ?>
                    <label class="miniatura-eliminable">
                        <img src="../<?= limpiar($foto['ruta_imagen']) ?>" alt="Foto de galería">
                        <span class="etiqueta-eliminar">
                            <input type="checkbox" name="eliminar_foto[]" value="<?= $foto['id'] ?>"> Eliminar
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <label for="galeria">Agregar más fotos a la galería (opcional, puedes elegir varias)</label>
            <input type="file" id="galeria" name="galeria[]" accept="image/*" multiple>

            <label for="latitud">Latitud</label>
            <input type="text" id="latitud" name="latitud" value="<?= $lugar['latitud'] ?? '' ?>">

            <label for="longitud">Longitud</label>
            <input type="text" id="longitud" name="longitud" value="<?= $lugar['longitud'] ?? '' ?>">

            <button type="submit"><?= $esEdicion ? 'Guardar cambios' : 'Crear lugar' ?></button>
        </form>
    </main>
</body>
</html>