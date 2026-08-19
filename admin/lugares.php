<?php
require '../includes/db.php';
require '../includes/funciones.php';
require '../includes/auth.php';
requerirLogin();

$stmt = $pdo->query("SELECT * FROM lugares ORDER BY nombre ASC");
$lugares = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionar lugares - Panel de administración</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header class="cabecera-admin">
        <h1>Gestionar lugares</h1>
        <p><a href="index.php" class="link-logout">← Volver al panel</a> · Hola, <?= limpiar($_SESSION['admin_usuario']) ?> — <a href="logout.php" class="link-logout">Cerrar sesión</a></p>
    </header>

    <main class="contenedor-admin-tabla">
        <a href="editar_lugar.php" class="boton-agregar boton-nuevo-lugar">+ Agregar lugar nuevo</a>

        <table class="tabla-admin">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoría</th>
                    <th>Reseñas</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lugares as $lugar): ?>
                    <?php
                        $stmtConteo = $pdo->prepare("SELECT COUNT(*) FROM resenas WHERE lugar_id = ?");
                        $stmtConteo->execute([$lugar['id']]);
                        $totalResenas = $stmtConteo->fetchColumn();
                    ?>
                    <tr>
                        <td><?= limpiar($lugar['nombre']) ?></td>
                        <td><?= limpiar($lugar['categoria']) ?></td>
                        <td><?= $totalResenas ?></td>
                        <td class="celda-acciones">
                            <a href="editar_lugar.php?id=<?= $lugar['id'] ?>" class="boton-tabla boton-editar">Editar</a>
                            <form method="POST" action="eliminar_lugar.php" class="form-eliminar-inline" onsubmit="return confirm('¿Seguro que quieres eliminar este lugar? Esto también borrará todas sus reseñas.');">
                                <input type="hidden" name="id" value="<?= $lugar['id'] ?>">
                                <button type="submit" class="boton-tabla boton-eliminar">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (count($lugares) === 0): ?>
                    <tr><td colspan="4">No hay lugares registrados todavía.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>