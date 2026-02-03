<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';

// Busca despesas
try {
    $stmt = $pdo->query("SELECT * FROM despesas ORDER BY data_despesa DESC");
    $despesas = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<p class='error'>Erro ao buscar despesas: " . $e->getMessage() . "</p>";
    $despesas = [];
}
?>

<div class="card">
    <h2>Gestão de Despesas</h2>

    <!-- Formulário para Adicionar Despesa -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Nova Despesa</h3>
        <form action="<?= BASE_URL ?>actions/salvar_despesa.php" method="POST">
            <div class="form-group">
                <label for="descricao">Descrição</label>
                <input type="text" name="descricao" id="descricao" required>
            </div>
            <div class="form-group">
                <label for="valor">Valor (R$)</label>
                <input type="number" step="0.01" name="valor" id="valor" required>
            </div>
            <div class="form-group">
                <label for="tipo">Tipo</label>
                <select name="tipo" id="tipo" required>
                    <option value="fixa">Fixa</option>
                    <option value="variavel">Variável</option>
                </select>
            </div>
            <div class="form-group">
                <label for="data_despesa">Data</label>
                <input type="date" name="data_despesa" id="data_despesa" required>
            </div>
            <button type="submit" class="btn btn-success">Salvar Despesa</button>
        </form>
    </div>

    <!-- Tabela de Despesas -->
    <h3 style="margin-top: 2rem;">Despesas Lançadas</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Descrição</th>
                <th>Valor</th>
                <th>Tipo</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($despesas) > 0): ?>
                <?php foreach ($despesas as $despesa): ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($despesa['data_despesa'])) ?></td>
                        <td><?= htmlspecialchars($despesa['descricao']) ?></td>
                        <td>R$ <?= number_format($despesa['valor'], 2, ',', '.') ?></td>
                        <td><?= ucfirst($despesa['tipo']) ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>actions/excluir_despesa.php?id=<?= $despesa['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja remover esta despesa?');">Remover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="5" style="text-align: center;">Nenhuma despesa registrada.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'views/footer.php'; ?>
