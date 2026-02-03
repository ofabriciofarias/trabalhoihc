<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';

// Filtro de data
$data_inicio = isset($_GET['inicio']) ? $_GET['inicio'] : date('Y-m-01');
$data_fim = isset($_GET['fim']) ? $_GET['fim'] : date('Y-m-t');

try {
    // Totais
    // Cálculo do Bruto (soma dos procedimentos vinculados aos atendimentos do período)
    $stmtBruto = $pdo->prepare("
        SELECT SUM(ap.valor_procedimento) 
        FROM atendimentos a 
        JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento 
        WHERE a.data_atendimento BETWEEN ? AND ?
    ");
    $stmtBruto->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $bruto = $stmtBruto->fetchColumn() ?? 0;

    // Cálculo do Líquido (soma do valor líquido da clínica registrado no atendimento)
    $stmtLiquido = $pdo->prepare("
        SELECT SUM(valor_liquido_clinica) 
        FROM atendimentos 
        WHERE data_atendimento BETWEEN ? AND ?
    ");
    $stmtLiquido->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $liquido = $stmtLiquido->fetchColumn() ?? 0;

    $financas = ['bruto' => $bruto, 'liquido' => $liquido];

    $stmtDespesas = $pdo->prepare("SELECT SUM(valor) as total FROM despesas WHERE data_despesa BETWEEN ? AND ?");
    $stmtDespesas->execute([$data_inicio, $data_fim]);
    $despesas = $stmtDespesas->fetchColumn();

    // Detalhes
    $stmtAtendimentos = $pdo->prepare("
        SELECT 
            a.id, a.data_atendimento, a.paciente_nome, a.valor_liquido_clinica, 
            u.nome as dentista, 
            GROUP_CONCAT(p.nome SEPARATOR ', ') as procedimento, 
            SUM(ap.valor_procedimento) as valor_bruto 
        FROM atendimentos a 
        JOIN usuarios u ON a.id_dentista = u.id 
        LEFT JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento 
        LEFT JOIN procedimentos p ON ap.id_procedimento = p.id 
        WHERE a.data_atendimento BETWEEN ? AND ? 
        GROUP BY a.id 
        ORDER BY a.data_atendimento DESC
    ");
    $stmtAtendimentos->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $atendimentos = $stmtAtendimentos->fetchAll();

    $stmtListaDespesas = $pdo->prepare("SELECT * FROM despesas WHERE data_despesa BETWEEN ? AND ? ORDER BY data_despesa DESC");
    $stmtListaDespesas->execute([$data_inicio, $data_fim]);
    $listaDespesas = $stmtListaDespesas->fetchAll();

    // Dados para o gráfico
    $stmtGrafico = $pdo->prepare("
        SELECT 
            d.dia,
            COALESCE(SUM(a.valor_liquido_clinica), 0) as faturamento,
            COALESCE(SUM(de.valor), 0) as despesa
        FROM 
            (SELECT CURDATE() - INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as dia
            FROM (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as a
            CROSS JOIN (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as b
            CROSS JOIN (select 0 as a union all select 1 union all select 2 union all select 3 union all select 4 union all select 5 union all select 6 union all select 7 union all select 8 union all select 9) as c
            ) d
        LEFT JOIN atendimentos a ON DATE(a.data_atendimento) = d.dia AND a.data_atendimento BETWEEN ? AND ?
        LEFT JOIN despesas de ON DATE(de.data_despesa) = d.dia AND de.data_despesa BETWEEN ? AND ?
        WHERE d.dia BETWEEN ? AND ?
        GROUP BY d.dia
        ORDER BY d.dia
    ");
    $stmtGrafico->execute([
        $data_inicio . ' 00:00:00', $data_fim . ' 23:59:59',
        $data_inicio, $data_fim,
        $data_inicio, $data_fim
    ]);
    $dadosGrafico = $stmtGrafico->fetchAll();

    // Preparar dados para o Chart.js
    $labels = [];
    $faturamentoData = [];
    $despesaData = [];
    $liquidoData = [];

    foreach ($dadosGrafico as $dado) {
        $labels[] = date('d/m', strtotime($dado['dia']));
        $faturamentoData[] = $dado['faturamento'];
        $despesaData[] = $dado['despesa'];
        $liquidoData[] = $dado['faturamento'] - $dado['despesa'];
    }

    // Dados para o gráfico de pizza de pagamentos
    $stmtPagamentos = $pdo->prepare("
        SELECT
            forma_pagamento,
            SUM(valor) as total
        FROM
            atendimento_pagamentos ap
        JOIN
            atendimentos a ON ap.id_atendimento = a.id
        WHERE
            a.data_atendimento BETWEEN ? AND ?
        GROUP BY
            forma_pagamento
    ");
    $stmtPagamentos->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $dadosPagamentos = $stmtPagamentos->fetchAll(PDO::FETCH_KEY_PAIR);

    $pagamentoLabels = [];
    $pagamentoData = [];
    if ($dadosPagamentos) {
        $pagamentoLabels = array_keys($dadosPagamentos);
        $pagamentoData = array_values($dadosPagamentos);
        // Capitalize labels for better readability
        $pagamentoLabels = array_map('ucfirst', $pagamentoLabels);
    }

} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    // Seta valores padrão para evitar erros na renderização
    $financas = ['bruto' => 0, 'liquido' => 0];
    $despesas = 0;
    $atendimentos = [];
    $listaDespesas = [];
    $labels = [];
    $faturamentoData = [];
    $despesaData = [];
    $liquidoData = [];
    $pagamentoLabels = [];
    $pagamentoData = [];
}
?>

<div class="card">
    <h2>Relatório Financeiro</h2>

    <form method="GET" action="relatorios.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center;">
            <div class="form-group">
                <label for="inicio">Data Início</label>
                <input type="date" name="inicio" id="inicio" value="<?= $data_inicio ?>">
            </div>
            <div class="form-group">
                <label for="fim">Data Fim</label>
                <input type="date" name="fim" id="fim" value="<?= $data_fim ?>">
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div class="dashboard-grid" style="margin-top: 2rem;">
        <div class="stat-card">
            <h3>Faturamento Bruto</h3>
            <div class="stat-value">R$ <?= number_format($financas['bruto'] ?? 0, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Total Despesas</h3>
            <div class="stat-value">R$ <?= number_format($despesas ?? 0, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3>Resultado Líquido</h3>
            <div class="stat-value">R$ <?= number_format(($financas['liquido'] ?? 0) - ($despesas ?? 0), 2, ',', '.') ?></div>
        </div>
    </div>

    <div class="chart-buttons" style="margin-top: 2rem; text-align: center; margin-bottom: 1rem; display: flex; justify-content: center; gap: 10px;">
        <button id="btnEvolucao" class="btn btn-primary">Ver Evolução Financeira</button>
        <button id="btnPagamentos" class="btn btn-secondary">Ver Distribuição de Pagamentos</button>
    </div>

    <div id="chart-evolucao-container" style="margin-top: 1rem;">
        <h3>Evolução Financeira</h3>
        <canvas id="evolucaoFinanceiraChart" style="max-height: 400px;"></canvas>
    </div>

    <div id="chart-pagamentos-container" style="margin-top: 1rem; display: none;">
        <h3>Distribuição de Pagamentos</h3>
        <canvas id="pagamentosChart" style="max-height: 400px;"></canvas>
    </div>

    <div style="margin-top: 3rem;">
        <h3>Detalhes de Atendimentos</h3>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Paciente</th>
                    <th>Procedimento</th>
                    <th>Valor Bruto</th>
                    <th>Valor Líquido</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($atendimentos as $at): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($at['data_atendimento'])) ?></td>
                    <td><?= htmlspecialchars($at['paciente_nome']) ?></td>
                    <td><?= htmlspecialchars($at['procedimento']) ?></td>
                    <td>R$ <?= number_format($at['valor_bruto'], 2, ',', '.') ?></td>
                    <td>R$ <?= number_format($at['valor_liquido_clinica'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="margin-top: 3rem;">
        <h3>Detalhes de Despesas</h3>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($listaDespesas as $dp): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($dp['data_despesa'])) ?></td>
                    <td><?= htmlspecialchars($dp['descricao']) ?></td>
                    <td><?= ucfirst($dp['tipo']) ?></td>
                    <td>R$ <?= number_format($dp['valor'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const btnEvolucao = document.getElementById('btnEvolucao');
    const btnPagamentos = document.getElementById('btnPagamentos');
    const evolucaoContainer = document.getElementById('chart-evolucao-container');
    const pagamentosContainer = document.getElementById('chart-pagamentos-container');

    // Botão para mostrar o gráfico de evolução
    btnEvolucao.addEventListener('click', () => {
        evolucaoContainer.style.display = 'block';
        pagamentosContainer.style.display = 'none';
        
        btnEvolucao.classList.add('btn-primary');
        btnEvolucao.classList.remove('btn-secondary');
        
        btnPagamentos.classList.add('btn-secondary');
        btnPagamentos.classList.remove('btn-primary');
    });

    // Botão para mostrar o gráfico de pagamentos
    btnPagamentos.addEventListener('click', () => {
        evolucaoContainer.style.display = 'none';
        pagamentosContainer.style.display = 'block';

        btnPagamentos.classList.add('btn-primary');
        btnPagamentos.classList.remove('btn-secondary');

        btnEvolucao.classList.add('btn-secondary');
        btnEvolucao.classList.remove('btn-primary');
    });
    const ctx = document.getElementById('evolucaoFinanceiraChart').getContext('2d');
    const evolucaoChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels) ?>,
            datasets: [
                {
                    label: 'Faturamento Líquido',
                    data: <?= json_encode($faturamentoData) ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Despesas',
                    data: <?= json_encode($despesaData) ?>,
                    borderColor: 'rgba(255, 99, 132, 1)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    fill: true,
                    tension: 0.1
                },
                {
                    label: 'Resultado',
                    data: <?= json_encode($liquidoData) ?>,
                    borderColor: 'rgba(54, 162, 235, 1)',
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    fill: true,
                    tension: 0.1
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value, index, values) {
                            return 'R$ ' + value.toLocaleString('pt-BR');
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed.y !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed.y);
                            }
                            return label;
                        }
                    }
                }
            }
        }
    });

    <?php if (!empty($pagamentoData)): ?>
    const ctxPagamentos = document.getElementById('pagamentosChart').getContext('2d');
    const pagamentosChart = new Chart(ctxPagamentos, {
        type: 'pie',
        data: {
            labels: <?= json_encode($pagamentoLabels) ?>,
            datasets: [{
                label: 'Total R$',
                data: <?= json_encode($pagamentoData) ?>,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.7)',  // Vermelho para Dinheiro/Pix
                    'rgba(54, 162, 235, 0.7)', // Azul para Débito
                    'rgba(255, 206, 86, 0.7)', // Amarelo para Crédito
                    'rgba(75, 192, 192, 0.7)', // Verde para outros
                    'rgba(153, 102, 255, 0.7)',// Roxo
                    'rgba(255, 159, 64, 0.7)'  // Laranja
                ],
                borderColor: '#fff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                },
                title: {
                    display: false,
                    text: 'Formas de Pagamento no Período'
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            if (label) {
                                label += ': ';
                            }
                            if (context.parsed !== null) {
                                label += new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.parsed);
                            }
                            return label;
                        },
                        footer: function(tooltipItems) {
                            let sum = tooltipItems[0].chart.getDatasetMeta(0).total;
                            let percentage = (tooltipItems[0].parsed * 100 / sum).toFixed(2) + '%';
                            return 'Porcentagem: ' + percentage;
                        }
                    }
                }
            }
        }
    });
    <?php endif; ?>
});
</script>

<?php require_once 'views/footer.php'; ?>
