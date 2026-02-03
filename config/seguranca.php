<?php
// config/seguranca.php
require_once __DIR__ . '/app.php';

// Se o usuário não estiver logado, redireciona para a página de login
if (!isset($_SESSION['usuario_id'])) {
    // Redireciona para a página de login usando a URL base
    header("Location: " . BASE_URL . "login.php");
    exit;
}
?>