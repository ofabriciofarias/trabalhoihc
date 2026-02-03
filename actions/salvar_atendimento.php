<?php
// actions/salvar_atendimento.php
require_once '../config/database.php';
require_once '../config/app.php'; // Para usar a BASE_URL
require_once 'Financeiro.php';

// Garantir o fuso horário correto para funções de data (NOW, date)
date_default_timezone_set('America/Sao_Paulo');

function send_json_error($message, $code = 400) {
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code($code);
    }
    echo json_encode(['sucesso' => false, 'erro' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pdo->beginTransaction();
    try {
        // --- INÍCIO: Lógica para Regra de Comissão ---
        // Buscar o faturamento bruto do mês corrente ANTES de adicionar o novo atendimento.
        // Isso é necessário para a regra de comissão variável.
        $data_inicio_mes = date('Y-m-01 00:00:00');
        $data_fim_mes = date('Y-m-t 23:59:59');

        $stmtFaturamento = $pdo->prepare(
            "SELECT SUM(ap.valor_procedimento) as total
             FROM atendimento_procedimentos ap
             JOIN atendimentos a ON ap.id_atendimento = a.id
             WHERE a.data_atendimento BETWEEN ? AND ?"
        );
        $stmtFaturamento->execute([$data_inicio_mes, $data_fim_mes]);
        $faturamentoBrutoMensal = $stmtFaturamento->fetchColumn() ?? 0;
        // --- FIM: Lógica para Regra de Comissão ---

        // 1. Receber dados do formulário
        $paciente = trim($_POST['paciente_nome'] ?? '');
        $paciente_telefone = !empty($_POST['paciente_telefone']) ? trim($_POST['paciente_telefone']) : null;
        $paciente_email = !empty($_POST['paciente_email']) ? trim($_POST['paciente_email']) : null;
        $idDentista = $_POST['id_dentista'] ?? null;
        $procedimentosInput = $_POST['procedimentos'] ?? [];
        $pagamentosInput = $_POST['pagamentos'] ?? [];

        // Validações básicas
        if (empty($paciente) || empty($idDentista) || empty($procedimentosInput['id'] ?? []) || empty($pagamentosInput['valor'] ?? [])) {
            send_json_error("Erro: Paciente, dentista, pelo menos um procedimento e um pagamento são obrigatórios.");
        }

        // 2. Processar procedimentos e calcular valor bruto total
        $valorBrutoTotal = 0;
        $procedimentosParaSalvar = [];
        $stmtProc = $pdo->prepare("SELECT id, nome, categoria, valor_base FROM procedimentos WHERE id = ?");

        foreach ($procedimentosInput['id'] as $key => $idProcedimento) {
            $quantidade = intval($procedimentosInput['quantidade'][$key]);
            if ($idProcedimento && $quantidade > 0) {
                $stmtProc->execute([$idProcedimento]);
                $procedimento = $stmtProc->fetch();
                if (!$procedimento) throw new Exception("Procedimento com ID $idProcedimento não encontrado.");
                
                $valorProcedimentoUnitario = $procedimento['valor_base'];

                // Verifica se um valor personalizado foi enviado e é um número válido
                if (isset($procedimentosInput['valor_personalizado'][$key]) && is_numeric($procedimentosInput['valor_personalizado'][$key]) && $procedimentosInput['valor_personalizado'][$key] !== '') {
                    $valorProcedimentoUnitario = floatval($procedimentosInput['valor_personalizado'][$key]);
                }

                $valorProcedimento = $valorProcedimentoUnitario * $quantidade;
                $valorBrutoTotal += $valorProcedimento;

                $custoProteticoManual = isset($procedimentosInput['custo_protetico'][$key]) ? floatval($procedimentosInput['custo_protetico'][$key]) : 0.0;

                $procedimentosParaSalvar[] = [
                    'id' => $idProcedimento, 
                    'quantidade' => $quantidade, 
                    'valor_total' => $valorProcedimento, 
                    'categoria' => $procedimento['categoria'],
                    'custo_protetico_manual' => $custoProteticoManual
                ];
            }
        }

        if ($valorBrutoTotal <= 0) {
            send_json_error("Erro: O valor total dos procedimentos deve ser positivo.");
        }

        // 3. Validar pagamentos
        $totalPago = 0;
        foreach ($pagamentosInput['valor'] as $valorPago) {
            $totalPago += floatval($valorPago);
        }

        if (abs($totalPago - $valorBrutoTotal) > 0.01) { // Tolerância para float
            send_json_error("A soma dos pagamentos (R$ " . number_format($totalPago, 2, ',', '.') . ") não corresponde ao valor total do atendimento (R$ " . number_format($valorBrutoTotal, 2, ',', '.') . ").");
        }
        
        // 4. Salvar o atendimento principal
        $sqlAtendimento = "INSERT INTO atendimentos (paciente_nome, paciente_telefone, paciente_email, id_dentista, data_atendimento) VALUES (?, ?, ?, ?, NOW())";
        $stmtAtendimento = $pdo->prepare($sqlAtendimento);
        $stmtAtendimento->execute([$paciente, $paciente_telefone, $paciente_email, $idDentista]);
        $idAtendimento = $pdo->lastInsertId();

        // 5. Salvar procedimentos e calcular comissões
        $totalComissaoDentista = 0;
        $totalCustoProtetico = 0;
        $sqlProcAtendimento = "INSERT INTO atendimento_procedimentos (id_atendimento, id_procedimento, quantidade, valor_procedimento, custo_protetico) VALUES (?, ?, ?, ?, ?)";
        $stmtProcAtendimento = $pdo->prepare($sqlProcAtendimento);

        foreach ($procedimentosParaSalvar as $proc) {
            $resComissao = Financeiro::calcularComissao($proc['valor_total'], $proc['categoria'], $faturamentoBrutoMensal, $proc['custo_protetico_manual']);
            
            $custoProteticoProcedimento = $resComissao['protetico'] ?? 0.0;

            $totalComissaoDentista += $resComissao['dentista'];
            $totalCustoProtetico += $custoProteticoProcedimento;
            $stmtProcAtendimento->execute([$idAtendimento, $proc['id'], $proc['quantidade'], $proc['valor_total'], $custoProteticoProcedimento]);
        }

        // 6. Salvar pagamentos e calcular taxas
        $totalTaxaCartao = 0;
        $sqlPagamento = "INSERT INTO atendimento_pagamentos (id_atendimento, forma_pagamento, valor, qtd_parcelas) VALUES (?, ?, ?, ?)";
        $stmtPagamento = $pdo->prepare($sqlPagamento);

        foreach ($pagamentosInput['forma'] as $key => $forma) {
            $valor = floatval($pagamentosInput['valor'][$key]);
            $parcelas = ($forma === 'credito') ? intval($pagamentosInput['parcelas'][$key]) : 1;
            
            $resMaquininha = Financeiro::calcularLiquidoMaquininha($valor, $forma, $parcelas);
            $totalTaxaCartao += $resMaquininha['valor_taxa'];

            $stmtPagamento->execute([$idAtendimento, $forma, $valor, $parcelas]);
        }

        // 7. Calcular valor líquido e atualizar atendimento
        $valorLiquidoClinica = $valorBrutoTotal - $totalTaxaCartao - $totalComissaoDentista - $totalCustoProtetico;

        $sqlUpdAtendimento = "UPDATE atendimentos SET valor_total = ?, taxa_cartao = ?, comissao_dentista = ?, custo_protetico = ?, valor_liquido_clinica = ? WHERE id = ?";
        $stmtUpdAtendimento = $pdo->prepare($sqlUpdAtendimento);
        $stmtUpdAtendimento->execute([$valorBrutoTotal, $totalTaxaCartao, $totalComissaoDentista, $totalCustoProtetico, $valorLiquidoClinica, $idAtendimento]);
        
        $pdo->commit();
        
        header('Content-Type: application/json');
        echo json_encode(['sucesso' => true, 'mensagem' => 'Atendimento lançado com sucesso!', 'redirectUrl' => BASE_URL . 'index.php']);
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        // Log aprimorado para facilitar a depuração
        $post_data = json_encode($_POST, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        error_log("Erro em salvar_atendimento.php (Code: " . $e->getCode() . "): " . $e->getMessage() . "\nDados recebidos:\n" . $post_data);
        send_json_error("Ocorreu um erro interno ao salvar o atendimento. Por favor, tente novamente.", 500);
    }
}
?>