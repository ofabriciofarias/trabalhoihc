<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';

// Pega a data da URL ou usa a data de hoje
$data_selecionada = isset($_GET['data']) ? $_GET['data'] : date('Y-m-d');

// Navegação
$data_obj = new DateTime($data_selecionada);
$data_anterior = (clone $data_obj)->modify('-1 day')->format('Y-m-d');
$data_posterior = (clone $data_obj)->modify('+1 day')->format('Y-m-d');

try {
    // 1. Faturamento Bruto
    $stmtBruto = $pdo->prepare("
        SELECT SUM(ap.valor_procedimento) as total 
        FROM atendimentos a
        JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
        WHERE DATE(a.data_atendimento) = ?
    ");
    $stmtBruto->execute([$data_selecionada]);
    $faturamento_bruto = $stmtBruto->fetchColumn() ?? 0;

    // 2. Taxas de Máquina
    $stmtTaxas = $pdo->prepare("SELECT SUM(taxa_cartao) as total FROM atendimentos WHERE DATE(data_atendimento) = ?");
    $stmtTaxas->execute([$data_selecionada]);
    $total_taxas = $stmtTaxas->fetchColumn() ?? 0;

    // 3. Pagamento por Dentista
    $stmtDentistas = $pdo->prepare("
        SELECT u.nome, SUM(a.comissao_dentista) as total_comissao
        FROM atendimentos a
        JOIN usuarios u ON a.id_dentista = u.id
        WHERE DATE(a.data_atendimento) = ?
        GROUP BY u.nome
        HAVING total_comissao > 0
        ORDER BY u.nome
    ");
    $stmtDentistas->execute([$data_selecionada]);
    $pagamento_dentistas = $stmtDentistas->fetchAll();
    $total_comissoes = array_sum(array_column($pagamento_dentistas, 'total_comissao'));

    // 4. Despesas do Dia
    $stmtDespesas = $pdo->prepare("SELECT * FROM despesas WHERE data_despesa = ? ORDER BY descricao");
    $stmtDespesas->execute([$data_selecionada]);
    $despesas_dia = $stmtDespesas->fetchAll();
    $total_despesas = array_sum(array_column($despesas_dia, 'valor'));

    // 5. Custo com Protético
    $stmtProtetico = $pdo->prepare("SELECT SUM(custo_protetico) as total FROM atendimentos WHERE DATE(data_atendimento) = ?");
    $stmtProtetico->execute([$data_selecionada]);
    $total_custo_protetico = $stmtProtetico->fetchColumn() ?? 0;

    // 6. Lucro Líquido
    $lucro_liquido = $faturamento_bruto - $total_taxas - $total_comissoes - $total_despesas - $total_custo_protetico;

} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    // Zera os valores em caso de erro
    $faturamento_bruto = 0;
    $total_taxas = 0;
    $pagamento_dentistas = [];
    $total_comissoes = 0;
    $despesas_dia = [];
    $total_despesas = 0;
    $total_custo_protetico = 0;
    $lucro_liquido = 0;
}
?>

<div class="card">
    <div style="display:flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <a href="?data=<?= $data_anterior ?>" class="btn btn-secondary">&lt; Dia Anterior</a>
        <h2>Relatório do Dia: <?= date('d/m/Y', strtotime($data_selecionada)) ?></h2>
        <a href="?data=<?= $data_posterior ?>" class="btn btn-secondary">Próximo Dia &gt;</a>
    </div>

    <form method="GET" action="relatorio_diario.php" class="card" style="margin-top: 1rem; margin-bottom: 2rem;">
        <div class="form-group" style="max-width: 300px; margin: auto;">
            <label for="data">Selecionar outra data</label>
            <input type="date" name="data" id="data" value="<?= $data_selecionada ?>" onchange="this.form.submit()">
        </div>
    </form>

    <div class="dashboard-grid">
        <div class="stat-card">
            <h3>Entrada Bruta</h3>
            <div class="stat-value" style="color: var(--primary-color);">R$ <?= number_format($faturamento_bruto, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Taxas de Cartão</h3>
            <div class="stat-value" style="color: var(--danger-color);">- R$ <?= number_format($total_taxas, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Custo Protético</h3>
            <div class="stat-value" style="color: var(--danger-color);">- R$ <?= number_format($total_custo_protetico, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--danger-color);">
            <h3>Saídas (Despesas)</h3>
            <div class="stat-value" style="color: var(--danger-color);">- R$ <?= number_format($total_despesas, 2, ',', '.') ?></div>
        </div>
        <div class="stat-card" style="border-left-color: var(--success-color);">
            <h3>Lucro Líquido do Dia</h3>
            <div class="stat-value">R$ <?= number_format($lucro_liquido, 2, ',', '.') ?></div>
        </div>
    </div>

    <div class="card" style="margin-top: 2rem;">
        <h3>Pagamentos por Dentista</h3>
        <table>
            <thead>
                <tr>
                    <th>Dentista</th>
                    <th>Valor a Pagar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($pagamento_dentistas) > 0): ?>
                    <?php foreach ($pagamento_dentistas as $dentista): ?>
                    <tr>
                        <td><?= htmlspecialchars($dentista['nome']) ?></td>
                        <td>R$ <?= number_format($dentista['total_comissao'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="2" style="text-align: center;">Nenhum atendimento comissionado no dia.</td></tr>
                <?php endif; ?>
            </tbody>
             <tfoot>
                <tr style="font-weight: bold;">
                    <td>Total Comissões</td>
                    <td style="color: var(--danger-color);">- R$ <?= number_format($total_comissoes, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="card" style="margin-top: 2rem;">
        <h3>Despesas Detalhadas do Dia</h3>
        <table>
            <thead>
                <tr>
                    <th>Descrição</th>
                    <th>Tipo</th>
                    <th>Valor</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($despesas_dia) > 0): ?>
                    <?php foreach ($despesas_dia as $despesa): ?>
                    <tr>
                        <td><?= htmlspecialchars($despesa['descricao']) ?></td>
                        <td><?= ucfirst($despesa['tipo']) ?></td>
                        <td>R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="3" style="text-align: center;">Nenhuma despesa registrada para este dia.</td></tr>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr style="font-weight: bold;">
                    <td colspan="2">Total Despesas</td>
                    <td>R$ <?= number_format($total_despesas, 2, ',', '.') ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
