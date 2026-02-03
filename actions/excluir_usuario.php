<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';

// Apenas proprietários podem excluir usuários
if ($_SESSION['usuario_perfil'] !== 'proprietario') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

require_once '../config/database.php';
require_once '../config/app.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Não permitir auto-exclusão
    if ($id === $_SESSION['usuario_id']) {
        header("Location: " . BASE_URL . "usuarios.php?erro=autoexclusao");
        exit;
    }

    try {
        // Verificar se o usuário (dentista) possui atendimentos
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM atendimentos WHERE id_dentista = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            header("Location: " . BASE_URL . "usuarios.php?erro=conflito_atendimento");
            exit;
        }

        $sql = "DELETE FROM usuarios WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        header("Location: " . BASE_URL . "usuarios.php?msg=excluido");
        exit;
    } catch (PDOException $e) {
        die("Erro ao excluir usuário: " . $e->getMessage());
    }
} else {
    header("Location: " . BASE_URL . "usuarios.php");
    exit;
}
?>
