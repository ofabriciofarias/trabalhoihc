<?php
// index.php (Dashboard)
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';

// Define o fuso horário para garantir que o mês atual seja exibido corretamente
date_default_timezone_set('America/Sao_Paulo');

// Define o período (Mês selecionado ou atual)
$mes_selecionado = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');

// Validação básica do formato YYYY-MM para segurança
if (!preg_match('/^\d{4}-\d{2}$/', $mes_selecionado)) {
    $mes_selecionado = date('Y-m');
}

$data_inicio = date('Y-m-01', strtotime($mes_selecionado));
$data_fim = date('Y-m-t', strtotime($mes_selecionado));

// Navegação entre meses
$mes_anterior = date('Y-m', strtotime($data_inicio . ' -1 month'));
$mes_proximo = date('Y-m', strtotime($data_inicio . ' +1 month'));

try {
        $stmtFinancas = $pdo->prepare("
        SELECT 
            SUM(ap.valor_procedimento) as total_bruto,
            SUM(a.valor_liquido_clinica) as total_lucro
        FROM atendimentos a
        JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
        WHERE a.data_atendimento BETWEEN ? AND ?
    ");
    $stmtFinancas->execute([$data_inicio . ' 00:00:00', $data_fim . ' 23:59:59']);
    $financas = $stmtFinancas->fetch();
    $lucroLiquido = $financas['total_lucro'] ?? 0;
    $faturamentoBruto = $financas['total_bruto'] ?? 0;

    // Total de Despesas no período
    $stmtDespesas = $pdo->prepare("SELECT SUM(valor) as total_despesas FROM despesas WHERE data_despesa BETWEEN ? AND ?");
    $stmtDespesas->execute([$data_inicio, $data_fim]);
    $totalDespesas = $stmtDespesas->fetchColumn() ?? 0;

    // Paginação e Busca
    $busca = isset($_GET['busca']) ? trim($_GET['busca']) : '';
    $pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
    if ($pagina < 1) $pagina = 1;
    $itensPorPagina = 10;
    $offset = ($pagina - 1) * $itensPorPagina;

    // Contagem total para paginação
    $sqlCount = "SELECT COUNT(DISTINCT a.id) FROM atendimentos a WHERE 1=1";
    $params = [];
    if (!empty($busca)) {
        $sqlCount .= " AND a.paciente_nome LIKE ?";
        $params[] = "%$busca%";
    }
    $stmtCount = $pdo->prepare($sqlCount);
    $stmtCount->execute($params);
    $totalRegistros = $stmtCount->fetchColumn();
    $totalPaginas = ceil($totalRegistros / $itensPorPagina);

    // Busca dos atendimentos paginados
    $sqlLista = "
        SELECT 
            a.id,
            a.data_atendimento,
            a.paciente_nome,
            u.nome as dentista,
            a.valor_liquido_clinica,
            GROUP_CONCAT(p.nome SEPARATOR ', ') as procedimentos
        FROM atendimentos a
        JOIN usuarios u ON a.id_dentista = u.id
        LEFT JOIN atendimento_procedimentos ap ON a.id = ap.id_atendimento
        LEFT JOIN procedimentos p ON ap.id_procedimento = p.id
        WHERE 1=1
    ";
    
    if (!empty($busca)) {
        $sqlLista .= " AND a.paciente_nome LIKE ?";
    }
    
    $sqlLista .= " GROUP BY a.id ORDER BY a.data_atendimento DESC LIMIT $itensPorPagina OFFSET $offset";

    $stmtLista = $pdo->prepare($sqlLista);
    $stmtLista->execute($params);
    $ultimosAtendimentos = $stmtLista->fetchAll();

} catch (Exception $e) {
    $lucroLiquido = 0;
    $faturamentoBruto = 0;
    $totalDespesas = 0;
    $ultimosAtendimentos = [];
    $totalPaginas = 0;
    echo "<p class='error'>Erro ao carregar dashboard: " . $e->getMessage() . "</p>";
}

// Formata o nome do mês em português
$formatter = new IntlDateFormatter(
    'pt_BR',
    IntlDateFormatter::FULL,
    IntlDateFormatter::NONE,
    'America/Sao_Paulo',
    IntlDateFormatter::GREGORIAN,
    'MMMM \'de\' yyyy'
);
$mesAtual = $formatter->format(strtotime($data_inicio));

?>

<style>
    /* Estilos para o Modal */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.6);
    }
    .modal-content {
        background-color: #fefefe;
        margin: 10% auto;
        margin: 5% auto;
        padding: 25px;
        border: 1px solid #888;
        width: 80%;
        max-width: 700px;
        border-radius: 8px;
        position: relative;
        box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        max-height: 85vh;
        display: flex;
        flex-direction: column;
    }
    .modal-close {
        color: #aaa;
        position: absolute;
        top: 10px;
        right: 20px;
        font-size: 28px;
        font-weight: bold;
        cursor: pointer;
    }
    .modal-footer {
        display: flex;
        justify-content: flex-end;
        margin-top: 2rem;
        padding-top: 1rem;
        border-top: 1px solid #eee;
    }
    #modalBody {
        overflow-y: auto;
        line-height: 1.6;
    }
    .modal-content .form-group {
        margin-bottom: 1rem;
    }
    .modal-content .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: bold;
        color: var(--text-muted);
    }
    .modal-content .form-group input[readonly],
    .modal-content .form-group textarea[readonly] {
        width: 100%;
        padding: 8px;
        border: 1px solid #ccc;
        background-color: #f8f9fa;
        border-radius: 4px;
    }
</style>

<div style="display:flex; justify-content:space-between; align-items:center;">
    <h1>Dashboard Financeiro</h1>
    <div style="display:flex; align-items:center; gap: 1rem;">
        <a href="?mes=<?= $mes_anterior ?>" class="btn btn-secondary" title="Mês Anterior">&lt;</a>
        <h2 style="color: var(--text-muted); margin: 0;"><?= ucfirst($mesAtual) ?></h2>
        <a href="?mes=<?= $mes_proximo ?>" class="btn btn-secondary" title="Próximo Mês">&gt;</a>
    </div>
</div>


<div class="dashboard-grid" style="margin-top: 2rem;">
    <div class="stat-card">
        <h3>Faturamento Bruto</h3>
        <div class="stat-value">R$ <?= number_format($faturamentoBruto, 2, ',', '.') ?></div>
        <p class="text-muted">Total transacionado no mês</p>
    </div>
    
    <div class="stat-card" style="border-left-color: var(--danger-color);">
        <h3>Total de Despesas</h3>
        <div class="stat-value">R$ <?= number_format($totalDespesas, 2, ',', '.') ?></div>
        <p class="text-muted">Soma de custos do mês</p>
    </div>

    <div class="stat-card" style="border-left-color: var(--success-color);">
        <h3>Resultado Líquido</h3>
        <div class="stat-value">R$ <?= number_format($lucroLiquido - $totalDespesas, 2, ',', '.') ?></div>
        <p class="text-muted">Lucro de atendimentos - despesas no mês</p>
    </div>
</div>

<div class="card" style="margin-top: 2rem;">
    <div style="display:flex; justify-content:space-between; align-items:center;">
        <h3>Histórico de Atendimentos</h3>
        <div style="display:flex; gap: 1rem; align-items:center;">
            <form method="GET" action="index.php" style="display:flex; gap: 0.5rem;">
                <input type="hidden" name="mes" value="<?= htmlspecialchars($mes_selecionado) ?>">
                <input type="text" name="busca" placeholder="Buscar por paciente..." value="<?= htmlspecialchars($busca ?? '') ?>" style="padding: 5px;">
                <button type="submit" class="btn btn-secondary">Buscar</button>
            </form>
            <a href="<?= BASE_URL ?>views/novo_atendimento.php" class="btn btn-primary">Novo Lançamento +</a>
        </div>
    </div>
    
    <table style="margin-top: 1rem;">
        <thead>
            <tr>
                <th>Data</th>
                <th>Paciente</th>
                <th>Procedimentos</th>
                <th>Dentista</th>
                <th>Valor Líquido (Clínica)</th>
            </tr>
        </thead>
        <tbody>
            <?php if(count($ultimosAtendimentos) > 0): ?>
                <?php foreach($ultimosAtendimentos as $at): ?>
                <tr class="clickable-row" 
                    data-id="<?= $at['id'] ?>"
                    data-data="<?= date('d/m/Y H:i', strtotime($at['data_atendimento'])) ?>"
                    data-paciente="<?= htmlspecialchars($at['paciente_nome']) ?>"
                    data-procedimentos="<?= htmlspecialchars($at['procedimentos'] ?? '') ?>"
                    data-dentista="<?= htmlspecialchars($at['dentista']) ?>"
                    data-valor="R$ <?= number_format($at['valor_liquido_clinica'], 2, ',', '.') ?>"
                    style="cursor: pointer;" title="Clique para ver detalhes">
                    <td><?= date('d/m/Y H:i', strtotime($at['data_atendimento'])) ?></td>
                    <td><?= htmlspecialchars($at['paciente_nome']) ?></td>
                    <td><?= htmlspecialchars($at['procedimentos'] ?? '') ?></td>
                    <td><?= htmlspecialchars($at['dentista']) ?></td>
                    <td style="color: green; font-weight: bold;">
                        R$ <?= number_format($at['valor_liquido_clinica'], 2, ',', '.') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="padding: 20px; text-align: center;">Nenhum atendimento registrado ainda.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Paginação -->
    <?php if ($totalPaginas > 1): ?>
    <div style="display: flex; justify-content: flex-end; margin-top: 1rem; gap: 0.5rem;">
        <?php for ($i = 1; $i <= $totalPaginas; $i++): ?>
            <?php 
                $active = $i === $pagina ? 'background-color: var(--primary-color); color: white;' : 'background-color: #eee; color: #333;';
                // Monta a URL mantendo os parâmetros existentes (mes, busca)
                $queryParams = $_GET;
                $queryParams['pagina'] = $i;
                $url = '?' . http_build_query($queryParams);
            ?>
            <a href="<?= $url ?>" style="padding: 5px 10px; text-decoration: none; border-radius: 4px; <?= $active ?>"><?= $i ?></a>
        <?php endfor; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Modal de Detalhes do Atendimento -->
<div id="detalhesModal" class="modal">
    <div class="modal-content">
        <span class="modal-close" id="modalCloseBtn">&times;</span>
        <h2>Detalhes do Atendimento</h2>
        <div id="modalBody" style="line-height: 1.6;">
            <!-- Conteúdo será preenchido via JS -->
        </div>
        <div class="modal-footer">
            <button id="modalFooterCloseBtn" class="btn btn-secondary">Fechar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('detalhesModal');
    const modalBody = document.getElementById('modalBody');
    const closeModalBtns = [document.getElementById('modalCloseBtn'), document.getElementById('modalFooterCloseBtn')];

    const closeModal = () => {
        modal.style.display = 'none';
        modalBody.innerHTML = ''; // Limpa o conteúdo ao fechar
    };

    closeModalBtns.forEach(btn => btn.addEventListener('click', closeModal));

    window.addEventListener('click', function(event) {
        if (event.target == modal) {
            closeModal();
        }
    });

    document.querySelectorAll('.clickable-row').forEach(row => {
        row.addEventListener('click', function() {
            // Simplificado para usar os dados já presentes na linha, evitando o erro de fetch.
            const data = this.dataset.data;
            const paciente = this.dataset.paciente;
            const procedimentos = this.dataset.procedimentos;
            const dentista = this.dataset.dentista;
            const valor = this.dataset.valor;

            // Monta o HTML do modal com formato de formulário (campos de leitura)
            const html = `
                <div class="form-group">
                    <label>Data do Atendimento</label>
                    <input type="text" value="${data}" readonly>
                </div>
                <div class="form-group">
                    <label>Paciente</label>
                    <input type="text" value="${paciente}" readonly>
                </div>
                <div class="form-group">
                    <label>Procedimentos</label>
                    <textarea readonly rows="3">${procedimentos}</textarea>
                </div>
                <div class="form-group">
                    <label>Dentista</label>
                    <input type="text" value="${dentista}" readonly>
                </div>
                <div class="form-group">
                    <label>Valor Líquido (Clínica)</label>
                    <input type="text" value="${valor}" readonly>
                </div>
            `;

            modalBody.innerHTML = html;
            modal.style.display = 'block';
        });
    });
});
</script>

<?php require_once 'views/footer.php'; ?>