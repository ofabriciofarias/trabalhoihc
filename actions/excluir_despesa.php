<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/app.php';

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    try {
        $sql = "DELETE FROM despesas WHERE id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$id]);

        header("Location: " . BASE_URL . "despesas.php?msg=excluido");
        exit;
    } catch (PDOException $e) {
        die("Erro ao excluir despesa: " . $e->getMessage());
    }
} else {
    header("Location: " . BASE_URL . "despesas.php");
    exit;
}
?>
