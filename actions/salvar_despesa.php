<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';
require_once '../config/database.php';
require_once '../config/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $descricao = trim($_POST['descricao']);
    $valor = floatval($_POST['valor']);
    $tipo = $_POST['tipo'];
    $data_despesa = $_POST['data_despesa'];

    if (empty($descricao) || $valor <= 0 || empty($tipo) || empty($data_despesa)) {
        die("Erro: Todos os campos são obrigatórios e o valor deve ser positivo.");
    }

    try {
        $sql = "INSERT INTO despesas (descricao, valor, tipo, data_despesa) VALUES (?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$descricao, $valor, $tipo, $data_despesa]);

        header("Location: " . BASE_URL . "despesas.php?msg=sucesso");
        exit;
    } catch (PDOException $e) {
        die("Erro ao salvar despesa: " . $e->getMessage());
    }
}
?>
