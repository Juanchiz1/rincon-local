<?php
require '../includes/db.php';
require '../includes/funciones.php';
require '../includes/auth.php';
requerirLogin();

$bannerActual = obtenerConfiguracion($pdo, 'banner_sitio', 'assets/banner_monteria_collage.jpg');
$errores = [];
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
        $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
        $extension = strtolower(pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION));
        $tamanoMaximo = 8 * 1024 * 1024;

        if (!in_array($extension, $extensionesPermitidas)) {
            $errores[] = 'El banner debe ser JPG, PNG o WEBP.';
        } elseif ($_FILES['banner']['size'] > $tamanoMaximo) {
            $errores[] = 'El banner no puede pesar más de 8MB.';
        } else {
            $nombreArchivo = uniqid('banner_') . '.' . $extension;
            $rutaDestino = __DIR__ . '/../uploads/' . $nombreArchivo;

            if (move_uploaded_file($_FILES['banner']['tmp_name'], $rutaDestino)) {
                $rutaNueva = 'uploads/' . $nombreArchivo;
                $stmt = $pdo->prepare("REPLACE INTO configuracion (clave, valor) VALUES ('banner_sitio', ?)");
                $stmt->execute([$rutaNueva]);
                $bannerActual = $rutaNueva;
                $exito = true;
            } else {
                $errores[] = 'No se pudo guardar el banner.';
            }
        }
    } else {
        $errores[] = 'Debes seleccionar una imagen.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cambiar banner - Panel de administración</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="cabecera-admin">
        <h1>Banner del sitio</h1>
        <p><a href="index.php" class="link-logout">← Volver al panel</a></p>
    </header>

    <main class="contenedor-formulario">
        <?php if ($exito): ?>
            <p class="mensaje-exito">Banner actualizado correctamente.</p>
        <?php endif; ?>

        <?php if (count($errores) > 0): ?>
            <ul class="mensaje-error">
                <?php foreach ($errores as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>

        <p class="etiqueta-banner-actual">Banner actual:</p>
        <img src="../<?= limpiar($bannerActual) ?>" alt="Banner actual" class="vista-previa-banner">

        <form method="POST" enctype="multipart/form-data" class="formulario-lugar">
            <label for="banner">Subir nuevo banner (reemplaza al actual)</label>
            <input type="file" id="banner" name="banner" accept="image/*" required>

            <button type="submit">Actualizar banner</button>
        </form>
    </main>
</body>
</html>