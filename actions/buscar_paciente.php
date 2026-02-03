<?php
require_once '../config/database.php';

header('Content-Type: application/json');

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit;
}

$term = '%' . trim($_GET['term']) . '%';

if (strlen(trim($_GET['term'])) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Esta query é um pouco mais complexa. Ela faz o seguinte:
    // 1. Encontra o ID do último atendimento para cada paciente que corresponde ao termo de busca.
    //    - Isso é feito com um sub-select e a função MAX(id).
    // 2. Junta o resultado com a própria tabela de atendimentos para pegar os detalhes completos (nome, telefone, email) do último atendimento.
    $stmt = $pdo->prepare(
        "SELECT a.paciente_nome as nome, a.paciente_telefone as telefone, a.paciente_email as email
         FROM atendimentos a
         INNER JOIN (
             SELECT paciente_nome, MAX(id) as max_id
             FROM atendimentos
             WHERE paciente_nome LIKE ?
             GROUP BY paciente_nome
         ) latest ON a.paciente_nome = latest.paciente_nome AND a.id = latest.max_id
         ORDER BY a.paciente_nome ASC
         LIMIT 10"
    );
    $stmt->execute([$term]);
    
    // Usamos FETCH_ASSOC para obter um array de objetos (associative arrays)
    $pacientes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($pacientes);

} catch (Exception $e) {
    // Em caso de erro, retorna um array vazio. Opcional: logar $e->getMessage()
    error_log("Erro em buscar_paciente.php: " . $e->getMessage());
    echo json_encode([]);
}
