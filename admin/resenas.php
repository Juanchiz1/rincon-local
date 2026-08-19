<?php
require '../includes/db.php';
require '../includes/funciones.php';
require '../includes/auth.php';
requerirLogin();

$stmt = $pdo->query(
    "SELECT resenas.*, lugares.nombre AS nombre_lugar
     FROM resenas
     JOIN lugares ON lugares.id = resenas.lugar_id
     ORDER BY resenas.creado_en DESC"
);
$resenas = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderar reseñas - Panel de administración</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="cabecera-admin">
        <h1>Moderar reseñas</h1>
        <p><a href="index.php" class="link-logout">← Volver al panel</a> · Hola, <?= limpiar($_SESSION['admin_usuario']) ?> — <a href="logout.php" class="link-logout">Cerrar sesión</a></p>
    </header>

    <main class="contenedor-admin-tabla">
        <?php if (count($resenas) === 0): ?>
            <p>No hay reseñas todavía.</p>
        <?php else: ?>
            <?php foreach ($resenas as $resena): ?>
                <article class="tarjeta-resena tarjeta-resena-admin">
                    <div class="cabecera-resena">
                        <strong><?= limpiar($resena['autor']) ?></strong>
                        <?php if ($resena['emoji']): ?>
                            <span class="emoji-resena"><?= $resena['emoji'] ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="lugar-de-la-resena">Sobre: <strong><?= limpiar($resena['nombre_lugar']) ?></strong></p>
                    <div class="detalle-ratings">
                        <span>Comida: <?= str_repeat('★', $resena['rating_comida']) . str_repeat('☆', 5 - $resena['rating_comida']) ?></span>
                        <span>Servicio: <?= str_repeat('★', $resena['rating_servicio']) . str_repeat('☆', 5 - $resena['rating_servicio']) ?></span>
                        <span>Ambiente: <?= str_repeat('★', $resena['rating_ambiente']) . str_repeat('☆', 5 - $resena['rating_ambiente']) ?></span>
                    </div>
                    <?php if ($resena['imagen']): ?>
                        <img src="../<?= limpiar($resena['imagen']) ?>" alt="Foto de la reseña" class="foto-resena">
                    <?php endif; ?>
                    <?php if ($resena['comentario']): ?>
                        <p class="comentario-resena"><?= $resena['comentario'] ?></p>
                    <?php endif; ?>
                    <time><?= date('d/m/Y', strtotime($resena['creado_en'])) ?></time>

                    <form method="POST" action="eliminar_resena.php" onsubmit="return confirm('¿Eliminar esta reseña?');">
                        <input type="hidden" name="id" value="<?= $resena['id'] ?>">
                        <button type="submit" class="boton-tabla boton-eliminar">Eliminar reseña</button>
                    </form>
                </article>
            <?php endforeach; ?>
        <?php endif; ?>
    </main>
</body>
</html>