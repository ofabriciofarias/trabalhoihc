<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';

// Apenas proprietários podem acessar esta página
if ($_SESSION['usuario_perfil'] !== 'proprietario') {
    header('Location: index.php');
    exit;
}

// Filtros
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$dentista_id = isset($_GET['dentista_id']) ? $_GET['dentista_id'] : 'todos';

// Define o período com base no mês selecionado
$data_inicio = date('Y-m-01', strtotime($mes));
$data_fim = date('Y-m-t', strtotime($mes));

try {
    // Busca todos os dentistas para o dropdown
    $stmtDentistas = $pdo->query("SELECT id, nome FROM usuarios WHERE perfil = 'dentista' ORDER BY nome");
    $dentistas = $stmtDentistas->fetchAll();

    // Monta a query base
    $sql = "
        SELECT
            u.id as dentista_id,
            u.nome as dentista_nome,
            COUNT(atendimento_agg.id) as total_atendimentos,
            SUM(atendimento_agg.faturamento_bruto) as faturamento_bruto,
            SUM(atendimento_agg.valor_liquido_clinica) as valor_para_clinica,
            SUM(atendimento_agg.comissao_dentista) as valor_para_dentista
        FROM usuarios u
        JOIN (
            SELECT
                a.id_dentista,
                a.id,
                a.valor_liquido_clinica,
                a.comissao_dentista,
                COALESCE((SELECT SUM(ap.valor_procedimento) FROM atendimento_procedimentos ap WHERE ap.id_atendimento = a.id), 0) as faturamento_bruto
            FROM atendimentos a
            WHERE a.data_atendimento BETWEEN :data_inicio AND :data_fim
        ) as atendimento_agg ON u.id = atendimento_agg.id_dentista
    ";
    
    $params = [
        'data_inicio' => $data_inicio . ' 00:00:00',
        'data_fim' => $data_fim . ' 23:59:59'
    ];

    // Adiciona filtro de dentista se não for 'todos'
    if ($dentista_id !== 'todos') {
        $sql .= " AND u.id = :dentista_id";
        $params['dentista_id'] = $dentista_id;
    }

    $sql .= " GROUP BY u.id, u.nome ORDER BY faturamento_bruto DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $relatorio_dentistas = $stmt->fetchAll();

} catch (Exception $e) {
    echo "<p class='error'>Erro ao gerar relatório: " . $e->getMessage() . "</p>";
    $relatorio_dentistas = [];
    $dentistas = [];
}
?>

<div class="card">
    <h2>Relatório de Desempenho por Dentista</h2>

    <form method="GET" action="relatorio_dentistas.php" class="card" style="margin-top: 1rem;">
        <div style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
            <div class="form-group">
                <label for="mes">Mês</label>
                <input type="month" name="mes" id="mes" value="<?= $mes ?>">
            </div>
            <div class="form-group">
                <label for="dentista_id">Dentista</label>
                <select name="dentista_id" id="dentista_id">
                    <option value="todos">Todos</option>
                    <?php foreach ($dentistas as $dentista): ?>
                        <option value="<?= $dentista['id'] ?>" <?= $dentista_id == $dentista['id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($dentista['nome']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Filtrar</button>
        </div>
    </form>

    <div style="margin-top: 2rem;">
        <table>
            <thead>
                <tr>
                    <th>Dentista</th>
                    <th>Nº de Atendimentos</th>
                    <th>Faturamento Bruto</th>
                    <th>Valor p/ Dentista</th>
                    <th>Valor p/ Clínica</th>
                </tr>
            </thead>
            <tbody>
                <?php if (count($relatorio_dentistas) > 0): ?>
                    <?php foreach($relatorio_dentistas as $rel): ?>
                    <tr>
                        <td><?= htmlspecialchars($rel['dentista_nome']) ?></td>
                        <td><?= $rel['total_atendimentos'] ?></td>
                        <td>R$ <?= number_format($rel['faturamento_bruto'], 2, ',', '.') ?></td>
                        <td style="color: var(--danger-color);"><?= number_format($rel['valor_para_dentista'], 2, ',', '.') ?></td>
                        <td style="color: var(--success-color); font-weight: bold;"><?= number_format($rel['valor_para_clinica'], 2, ',', '.') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 20px;">Nenhum dado encontrado para os filtros selecionados.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'views/footer.php'; ?>
