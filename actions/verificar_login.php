<?php
// actions/verificar_login.php
require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/app.php'; // Para usar a BASE_URL

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $senha = $_POST['senha'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE login = ?");
        $stmt->execute([$login]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($senha, $usuario['senha'])) {
            // Login bem-sucedido
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['usuario_nome'] = $usuario['nome'];
            $_SESSION['usuario_perfil'] = $usuario['perfil'];
            header("Location: " . BASE_URL . "index.php");
            exit;
        } else {
            // Login inválido
            header("Location: " . BASE_URL . "login.php?erro=1");
            exit;
        }
    } catch (PDOException $e) {
        die("Erro na verificação de login: " . $e->getMessage());
    }
}
?>
