<?php
require '../includes/db.php';
require '../includes/funciones.php';
require '../includes/auth.php';
requerirLogin();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de administración - RincónLocal</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="cabecera-admin">
        <h1>Panel de administración</h1>
        <p>Hola, <?= limpiar($_SESSION['admin_usuario']) ?> — <a href="logout.php" class="link-logout">Cerrar sesión</a></p>
    </header>

    <main class="panel-admin">
        <a href="lugares.php" class="tarjeta-panel">
        <h2>📍 Lugares</h2>
            <p>Editar o eliminar lugares registrados</p>
        </a>
        <a href="resenas.php" class="tarjeta-panel">
    <h2>💬 Reseñas</h2>
            <p>Moderar comentarios de usuarios</p>
        </a>
        <a href="#" class="tarjeta-panel">
            <h2>🖼️ Banner</h2>
            <p>Cambiar la foto de la cabecera principal</p>
        </a>
    </main>
</body>
</html>