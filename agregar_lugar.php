<?php
require 'includes/db.php';
require 'includes/funciones.php';

$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = limpiar($_POST['nombre'] ?? '');
    $categoria = limpiar($_POST['categoria'] ?? '');
    $direccion = limpiar($_POST['direccion'] ?? '');
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

    // Manejo de la imagen, solo si el usuario subió una.
    if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
        $tamanoMaximo = 5 * 1024 * 1024; // 5 MB

        if (!in_array($extension, $extensionesPermitidas)) {
            $errores[] = 'La imagen debe ser JPG, PNG o WEBP.';
        } elseif ($_FILES['imagen']['size'] > $tamanoMaximo) {
            $errores[] = 'La imagen no puede pesar más de 5MB.';
        } else {
            $nombreImagen = uniqid('lugar_') . '.' . $extension;
            $rutaDestino = __DIR__ . '/uploads/' . $nombreImagen;

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
                $errores[] = 'No se pudo guardar la imagen, intenta de nuevo.';
                $nombreImagen = null;
            }
        }
    }

    if (count($errores) === 0) {
        $stmt = $pdo->prepare(
            "INSERT INTO lugares (nombre, categoria, direccion, imagen, latitud, longitud)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $nombre,
            $categoria,
            $direccion,
            $nombreImagen,
            $latitud !== '' ? $latitud : null,
            $longitud !== '' ? $longitud : null,
        ]);

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
</head>
<body>
    <header class="cabecera">
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

                <label for="imagen">Foto del lugar (opcional)</label>
                <input type="file" id="imagen" name="imagen" accept="image/*">

                <label for="latitud">Latitud (opcional)</label>
                <input type="text" id="latitud" name="latitud" placeholder="8.7500" value="<?= $_POST['latitud'] ?? '' ?>">

                <label for="longitud">Longitud (opcional)</label>
                <input type="text" id="longitud" name="longitud" placeholder="-75.8800" value="<?= $_POST['longitud'] ?? '' ?>">

                <button type="submit">Guardar lugar</button>
            </form>
        <?php endif; ?>
    </main>
</body>
</html>