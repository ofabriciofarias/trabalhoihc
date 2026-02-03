<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/app.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        // Verificar se o procedimento está sendo usado em algum atendimento
        $stmtCheck = $pdo->prepare("SELECT COUNT(*) FROM atendimento_procedimentos WHERE id_procedimento = ?");
        $stmtCheck->execute([$id]);
        if ($stmtCheck->fetchColumn() > 0) {
            header("Location: " . BASE_URL . "procedimentos.php?erro=conflito");
            exit;
        }

        $sql = "DELETE FROM procedimentos WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        header("Location: " . BASE_URL . "procedimentos.php?msg=excluido");
        exit;
    } catch (PDOException $e) {
        die("Erro ao excluir procedimento: " . $e->getMessage());
    }
} else {
    header("Location: " . BASE_URL . "procedimentos.php");
    exit;
}
?>
