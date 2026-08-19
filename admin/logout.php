<?php
require '../includes/auth.php';
$_SESSION = [];
session_destroy();
header('Location: login.php');
exit;