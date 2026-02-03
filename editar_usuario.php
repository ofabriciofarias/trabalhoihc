<?php
require_once 'config/session.php';
require_once 'config/seguranca.php';

// Apenas proprietários podem acessar esta página
if ($_SESSION['usuario_perfil'] !== 'proprietario') {
    header("Location: index.php");
    exit;
}

require_once 'config/database.php';
require_once 'views/header.php';

// Validação do ID
if (!isset($_GET['id']) || !filter_var($_GET['id'], FILTER_VALIDATE_INT)) {
    echo "<p class='error'>ID de usuário inválido.</p>";
    require_once 'views/footer.php';
    exit;
}

$user_id = $_GET['id'];

// Busca dados do usuário
try {
    $stmt = $pdo->prepare("SELECT id, nome, login, perfil FROM usuarios WHERE id = ?");
    $stmt->execute([$user_id]);
    $usuario = $stmt->fetch();

    if (!$usuario) {
        echo "<p class='error'>Usuário não encontrado.</p>";
        require_once 'views/footer.php';
        exit;
    }
} catch (Exception $e) {
    echo "<p class='error'>Erro ao buscar usuário: " . $e->getMessage() . "</p>";
    require_once 'views/footer.php';
    exit;
}
?>

<div class="card">
    <h2>Editar Usuário</h2>

    <?php if (isset($_GET['erro']) && $_GET['erro'] === 'login_duplicado'): ?>
        <p class='error'>O login informado já está em uso por outro usuário.</p>
    <?php endif; ?>

    <form action="<?= BASE_URL ?>actions/salvar_usuario.php" method="POST" style="margin-top: 1rem;">
        <input type="hidden" name="id" value="<?= $usuario['id'] ?>">
        
        <div class="form-group">
            <label for="nome">Nome Completo</label>
            <input type="text" name="nome" id="nome" value="<?= htmlspecialchars($usuario['nome']) ?>" required>
        </div>
        
        <div class="form-group">
            <label for="login">Login</label>
            <input type="text" name="login" id="login" value="<?= htmlspecialchars($usuario['login']) ?>" required>
        </div>

        <div class="form-group">
            <label for="senha">Nova Senha (deixe em branco para não alterar)</label>
            <input type="password" name="senha" id="senha">
        </div>

        <div class="form-group">
            <label for="perfil">Perfil</label>
            <select name="perfil" id="perfil" required>
                <option value="recepcionista" <?= $usuario['perfil'] === 'recepcionista' ? 'selected' : '' ?>>Recepcionista</option>
                <option value="dentista" <?= $usuario['perfil'] === 'dentista' ? 'selected' : '' ?>>Dentista</option>
                <option value="proprietario" <?= $usuario['perfil'] === 'proprietario' ? 'selected' : '' ?>>Proprietário</option>
            </select>
        </div>

        <div style="margin-top: 2rem;">
            <button type="submit" class="btn btn-success">Salvar Alterações</button>
            <a href="usuarios.php" class="btn btn-secondary">Cancelar</a>
        </div>
    </form>
</div>

<?php require_once 'views/footer.php'; ?>
