<?php
// setup_data.php
require_once 'config/database.php';

try {
    echo "Iniciando cadastro de dados padrão...<br>";

    // 1. Criar Dentistas e Proprietário
    $stmt = $pdo->query("SELECT COUNT(*) FROM usuarios");
    if ($stmt->fetchColumn() == 0) {
        // Hash das senhas
        $senhaRoberto = password_hash('123', PASSWORD_BCRYPT);
        $senhaAna = password_hash('123', PASSWORD_BCRYPT);
        $senhaAdmin = password_hash('admin123', PASSWORD_BCRYPT);

        $sql = "INSERT INTO usuarios (nome, login, senha, perfil) VALUES 
                ('Administrador', 'admin', ?, 'proprietario'),
                ('Dr. Roberto Silva', 'roberto', ?, 'dentista'),
                ('Dra. Ana Costa', 'ana', ?, 'dentista')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$senhaAdmin, $senhaRoberto, $senhaAna]);
        echo "Usuários (Proprietário e Dentistas) cadastrados com senhas seguras.<br>";
    }

    // 2. Criar Procedimentos
    $stmt = $pdo->query("SELECT COUNT(*) FROM procedimentos");
    if ($stmt->fetchColumn() == 0) {
        $sql = "INSERT INTO procedimentos (nome, categoria, valor_base) VALUES 
                ('Limpeza Completa', 'geral', 150.00),
                ('Restauração Simples', 'geral', 200.00),
                ('Canal (Endodontia)', 'especializado', 800.00),
                ('Implante Unitário', 'especializado', 2500.00),
                ('Prótese Total', 'protese', 1800.00)";
        $pdo->exec($sql);
        echo "Procedimentos cadastrados.<br>";
    }

    echo "<strong>Configuração concluída! <a href='index.php'>Ir para o Dashboard</a></strong>";

} catch (PDOException $e) {
    die("Erro: " . $e->getMessage());
}
?>