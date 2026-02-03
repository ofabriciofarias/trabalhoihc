<?php
// actions/logout.php
require_once '../config/session.php';
require_once '../config/app.php'; // Para usar a BASE_URL

session_destroy();
header("Location: " . BASE_URL . "login.php");
exit;
?>