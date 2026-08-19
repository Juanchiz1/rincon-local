<?php
require '../includes/db.php';
require '../includes/funciones.php';
require '../includes/auth.php';

if (estaLogueado()) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM administradores WHERE usuario = ?");
    $stmt->execute([$usuario]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_usuario'] = $admin['usuario'];
        header('Location: index.php');
        exit;
    }

    $error = 'Usuario o contraseña incorrectos.';
}
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
        <p>RincónLocal</p>
    </header>

    <main class="contenedor-formulario">
        <?php if ($error): ?>
            <p class="mensaje-error-simple"><?= $error ?></p>
        <?php endif; ?>

        <form method="POST" class="formulario-lugar">
            <label for="usuario">Usuario</label>
            <input type="text" id="usuario" name="usuario" required autofocus>

            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required>

            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>