<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function estaLogueado()
{
    return isset($_SESSION['admin_id']);
}

function requerirLogin()
{
    if (!estaLogueado()) {
        header('Location: login.php');
        exit;
    }
}