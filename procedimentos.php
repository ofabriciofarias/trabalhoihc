<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';
require_once 'config/database.php';
require_once 'views/header.php';

// Busca procedimentos
try {
    $stmt = $pdo->query("SELECT * FROM procedimentos ORDER BY nome ASC");
    $procedimentos = $stmt->fetchAll();
} catch (Exception $e) {
    echo "<p class='error'>Erro ao buscar procedimentos: " . $e->getMessage() . "</p>";
    $procedimentos = [];
}
?>

<div class="card">
    <h2>Gestão de Procedimentos</h2>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'conflito'): ?>
        <p class="error">Não é possível excluir o procedimento, pois ele já está vinculado a um ou mais atendimentos.</p>
    <?php elseif (isset($_GET['msg']) && $_GET['msg'] === 'sucesso'): ?>
        <p style="color: green; background: #e8f5e9; padding: 1rem; border-radius: 6px;">Procedimento salvo com sucesso!</p>
    <?php endif; ?>

    <!-- Formulário para Adicionar Procedimento -->
    <div class="card" style="margin-top: 2rem;">
        <h3>Novo Procedimento</h3>
        <form action="<?= BASE_URL ?>actions/salvar_procedimento.php" method="POST">
            <div class="form-group">
                <label for="nome">Nome do Procedimento</label>
                <input type="text" name="nome" id="nome" required>
            </div>
            <div class="form-group">
                <label for="categoria">Categoria</label>
                <select name="categoria" id="categoria" required>
                    <option value="geral">Geral</option>
                    <option value="especializado">Especializado</option>
                    <option value="protese">Prótese</option>
                </select>
            </div>
            <div class="form-group">
                <label for="valor_base">Valor Base (R$)</label>
                <input type="number" step="0.01" name="valor_base" id="valor_base">
            </div>
            <button type="submit" class="btn btn-success">Salvar Procedimento</button>
        </form>
    </div>

    <!-- Tabela de Procedimentos -->
    <h3 style="margin-top: 2rem;">Procedimentos Cadastrados</h3>
    <table>
        <thead>
            <tr>
                <th>Nome</th>
                <th>Categoria</th>
                <th>Valor Base</th>
                <th>Ações</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($procedimentos) > 0): ?>
                <?php foreach ($procedimentos as $procedimento): ?>
                    <tr>
                        <td><?= htmlspecialchars($procedimento['nome']) ?></td>
                        <td><?= ucfirst($procedimento['categoria']) ?></td>
                        <td>R$ <?= number_format($procedimento['valor_base'], 2, ',', '.') ?></td>
                        <td>
                            <a href="<?= BASE_URL ?>actions/excluir_procedimento.php?id=<?= $procedimento['id'] ?>" class="btn btn-danger" onclick="return confirm('Tem certeza que deseja remover este procedimento?');">Remover</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="4" style="text-align: center;">Nenhum procedimento registrado.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require_once 'views/footer.php'; ?>
