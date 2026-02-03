<?php
require_once '../config/session.php';
require_once '../config/seguranca.php';

// Apenas proprietários podem salvar usuários
if ($_SESSION['usuario_perfil'] !== 'proprietario') {
    header("Location: " . BASE_URL . "index.php");
    exit;
}

require_once '../config/database.php';
require_once '../config/app.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? null;
    $nome = trim($_POST['nome']);
    $login = trim($_POST['login']);
    $senha = $_POST['senha'];
    $perfil = $_POST['perfil'];

    if (empty($nome) || empty($login) || empty($perfil)) {
        die("Erro: Nome, login e perfil são obrigatórios.");
    }

    try {
        // Se o ID existe, é uma atualização
        if ($id) {
            $sql = "UPDATE usuarios SET nome = ?, login = ?, perfil = ?";
            $params = [$nome, $login, $perfil];

            // Apenas atualiza a senha se uma nova for fornecida
            if (!empty($senha)) {
                $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
                $sql .= ", senha = ?";
                $params[] = $senhaHash;
            }

            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
        
        // Se não tem ID, é um novo usuário
        } else {
            if (empty($senha)) {
                die("Erro: Senha é obrigatória para novos usuários.");
            }
            $senhaHash = password_hash($senha, PASSWORD_BCRYPT);
            $sql = "INSERT INTO usuarios (nome, login, senha, perfil) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$nome, $login, $senhaHash, $perfil]);
        }

        header("Location: " . BASE_URL . "usuarios.php?msg=sucesso");
        exit;

    } catch (PDOException $e) {
        // Tratar erro de login duplicado
        if ($e->getCode() == 23000) {
            $redirect_url = $id ? "editar_usuario.php?id=$id&erro=login_duplicado" : "usuarios.php?erro=login_duplicado";
            header("Location: " . BASE_URL . $redirect_url);
        } else {
            die("Erro ao salvar usuário: " . $e->getMessage());
        }
    }
}
?>
