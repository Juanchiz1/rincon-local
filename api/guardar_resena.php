<?php
header('Content-Type: application/json; charset=utf-8');

require '../includes/db.php';
require '../includes/funciones.php';

$respuesta = ['exito' => false, 'errores' => []];

$lugarId = $_POST['lugar_id'] ?? '';
$autorCrudo = trim($_POST['autor'] ?? '');
$comentarioCrudo = trim($_POST['comentario'] ?? '');
$autor = limpiar($autorCrudo);
$comentario = limpiar($comentarioCrudo);
$emoji = $_POST['emoji'] ?? '';
$ratingComida = $_POST['rating_comida'] ?? '';
$ratingServicio = $_POST['rating_servicio'] ?? '';
$ratingAmbiente = $_POST['rating_ambiente'] ?? '';
$emojisPermitidos = ['😍', '😊', '😐', '😕', '😡'];

if (!$lugarId || !is_numeric($lugarId)) {
    $respuesta['errores'][] = 'Lugar inválido.';
}
if ($autor === '') {
    $respuesta['errores'][] = 'Tu nombre es obligatorio.';
}
foreach (['rating_comida' => $ratingComida, 'rating_servicio' => $ratingServicio, 'rating_ambiente' => $ratingAmbiente] as $valor) {
    if (!in_array($valor, ['1', '2', '3', '4', '5'], true)) {
        $respuesta['errores'][] = 'Debes calificar todas las categorías con estrellas.';
        break;
    }
}
if ($emoji !== '' && !in_array($emoji, $emojisPermitidos, true)) {
    $emoji = '';
}

$imagenResena = null;
if (isset($_FILES['imagen']) && $_FILES['imagen']['error'] === UPLOAD_ERR_OK) {
    $extensionesPermitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $extension = strtolower(pathinfo($_FILES['imagen']['name'], PATHINFO_EXTENSION));
    $tamanoMaximo = 5 * 1024 * 1024;

    if (!in_array($extension, $extensionesPermitidas)) {
        $respuesta['errores'][] = 'La foto debe ser JPG, PNG o WEBP.';
    } elseif ($_FILES['imagen']['size'] > $tamanoMaximo) {
        $respuesta['errores'][] = 'La foto no puede pesar más de 5MB.';
    } else {
        $nombreArchivo = uniqid('resena_') . '.' . $extension;
        $rutaDestino = __DIR__ . '/../uploads/' . $nombreArchivo;

        if (move_uploaded_file($_FILES['imagen']['tmp_name'], $rutaDestino)) {
            $imagenResena = 'uploads/' . $nombreArchivo;
        }
    }
}

if (count($respuesta['errores']) > 0) {
    http_response_code(400);
    echo json_encode($respuesta);
    exit;
}

$stmt = $pdo->prepare(
    "INSERT INTO resenas (lugar_id, autor, comentario, emoji, imagen, rating_comida, rating_servicio, rating_ambiente)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->execute([
    $lugarId, $autor, $comentario, $emoji !== '' ? $emoji : null, $imagenResena,
    $ratingComida, $ratingServicio, $ratingAmbiente,
]);

// Recalculamos con TODAS las reseñas del lugar, incluida la recién insertada.
$stmtResenas = $pdo->prepare("SELECT * FROM resenas WHERE lugar_id = ?");
$stmtResenas->execute([$lugarId]);
$resenas = $stmtResenas->fetchAll();

$respuesta['exito'] = true;
$respuesta['resena'] = [
    'autor' => $autorCrudo,
    'comentario' => $comentarioCrudo,
    'emoji' => $emoji,
    'imagen' => $imagenResena,
    'rating_comida' => (int) $ratingComida,
    'rating_servicio' => (int) $ratingServicio,
    'rating_ambiente' => (int) $ratingAmbiente,
    'fecha' => date('d/m/Y'),
];
$respuesta['total_resenas'] = count($resenas);
$respuesta['promedio_general'] = promedioResenas($resenas);
$respuesta['promedios_categoria'] = promediosPorCategoria($resenas);

echo json_encode($respuesta);