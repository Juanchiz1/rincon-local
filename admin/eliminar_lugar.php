<?php
require '../includes/db.php';
require '../includes/auth.php';
requerirLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && is_numeric($_POST['id'])) {
    $stmt = $pdo->prepare("DELETE FROM lugares WHERE id = ?");
    $stmt->execute([$_POST['id']]);
}

header('Location: lugares.php');
exit;