<?php
// 1. Inicia o buffer de saída para capturar qualquer saída inesperada (warnings, die(), etc.)
ob_start();

// Função para enviar uma resposta de erro JSON padronizada e encerrar o script
function send_json_error($message, $code = 500, $log_message = '') {
    // Limpa qualquer saída que possa ter sido gerada antes do erro
    while (ob_get_level() > 0) {
        ob_end_clean();
    }

    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($code);
    }

    if (!empty($log_message)) {
        error_log($log_message);
    }

    echo json_encode(['erro' => $message]);
    exit;
}

try {
    require_once '../config/session.php';
    require_once '../config/database.php';

    // 2. Verifica se os includes produziram alguma saída (ex: o 'die()' do database.php)
    $stray_output = ob_get_clean();
    if (!empty($stray_output)) {
        // Se houve saída, é um erro. Lançamos uma exceção para ser tratada pelo catch.
        throw new Exception("Saída inesperada durante a inicialização: " . trim($stray_output));
    }
    ob_start(); // Reinicia o buffer para o resto do script, por segurança.

    // 3. Verificações de segurança e de parâmetros
    if (!isset($_SESSION['usuario_id'])) {
        send_json_error('Sessão expirada. Por favor, faça o login novamente.', 401);
    }

    if (!isset($pdo)) {
        throw new Exception("A conexão com o banco de dados não foi estabelecida.");
    }

    if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
        send_json_error('ID de atendimento inválido.', 400);
    }

    $idAtendimento = $_GET['id'];
    $response = [];

    // 1. Buscar dados principais do atendimento
    $stmtMain = $pdo->prepare("
        SELECT 
            a.paciente_nome, 
            a.data_atendimento, 
            u.nome as dentista,
            a.taxa_cartao,
            a.comissao_dentista,
            a.custo_protetico,
            a.valor_liquido_clinica
        FROM atendimentos a
        JOIN usuarios u ON a.id_dentista = u.id
        WHERE a.id = ?
    ");
    $stmtMain->execute([$idAtendimento]);
    $atendimento = $stmtMain->fetch();

    if (!$atendimento) {
        send_json_error('Atendimento não encontrado.', 404);
    }
    $response['atendimento'] = $atendimento;

    // 2. Buscar procedimentos e valor bruto total
    $stmtProcs = $pdo->prepare("
        SELECT p.nome, ap.quantidade, ap.valor_procedimento
        FROM atendimento_procedimentos ap
        JOIN procedimentos p ON ap.id_procedimento = p.id
        WHERE ap.id_atendimento = ?
    ");
    $stmtProcs->execute([$idAtendimento]);
    $procedimentos = $stmtProcs->fetchAll();
    $response['procedimentos'] = $procedimentos;
    
    $valorBrutoTotal = array_sum(array_column($procedimentos, 'valor_procedimento'));
    $response['valor_bruto_total'] = $valorBrutoTotal;

    // 3. Buscar formas de pagamento
    $stmtPagamentos = $pdo->prepare("
        SELECT forma_pagamento, valor, qtd_parcelas
        FROM atendimento_pagamentos
        WHERE id_atendimento = ?
    ");
    $stmtPagamentos->execute([$idAtendimento]);
    $pagamentos = $stmtPagamentos->fetchAll();
    $response['pagamentos'] = $pagamentos;

    // 5. Enviar resposta de sucesso
    ob_end_clean(); // Descarta o buffer de segurança
    header('Content-Type: application/json');
    echo json_encode($response);

} catch (Throwable $e) {
    // 6. Bloco de captura universal para qualquer erro/exceção
    $log_message = "Erro em get_atendimento_details.php: " . $e->getMessage() . " em " . $e->getFile() . " na linha " . $e->getLine();
    send_json_error('Ocorreu um erro no servidor ao processar sua solicitação.', 500, $log_message);
}
?>