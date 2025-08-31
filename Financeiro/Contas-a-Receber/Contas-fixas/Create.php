<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 3) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 3) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 3) . '/conexao.php';

header("Content-Type: application/json; charset=UTF-8");

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método não permitido. Use POST."
    ]);
    exit;
}

$data = json_decode(file_get_contents("php://input"), true);

// Sanitização
$nome           = trim($data['nome'] ?? '');
$descricao      = trim($data['descricao'] ?? '');
$categoria      = trim('Conta Fixa' ?? '');
$valor          = trim($data['valor'] ?? '');
$recorrencia    = intval($data['recorrencia'] ?? 0);
$dia_vencimento = intval($data['dia_vencimento'] ?? 0);
$data_fim       = !empty($data['data_fim']) ? date('Y-m-d', strtotime($data['data_fim'])) : null;

// Setores válidos
$setoresValidos = ['Conta Fixa'];

// Validações básicas
if (!$nome || !$categoria || !$valor || !$recorrencia || !$dia_vencimento) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Campos obrigatórios faltando ou inválidos."
    ]);
    exit;
}

if (!in_array($categoria, $setoresValidos)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Categoria inválida. Use apenas: " . implode(', ', $setoresValidos)
    ]);
    exit;
}

try {
    global $pdo;

    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS financeiro_contas_fixas (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao VARCHAR(255),
            categoria VARCHAR(50) NOT NULL DEFAULT 'Conta Fixa',
            valor DECIMAL(10,2) NOT NULL,
            recorrencia INT NOT NULL COMMENT 'em meses',
            dia_vencimento INT NOT NULL COMMENT 'dia do mês',
            data_fim DATE NULL COMMENT 'data limite para gerar a recorrência',
            ultima_execucao DATE NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Inserir registro
    $sql = "INSERT INTO financeiro_contas_fixas 
                (nome, descricao, categoria, valor, recorrencia, dia_vencimento, data_fim) 
            VALUES 
                (:nome, :descricao, :categoria, :valor, :recorrencia, :dia_vencimento, :data_fim)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome'          => $nome,
        ':descricao'     => $descricao,
        ':categoria'     => $categoria,
        ':valor'         => $valor,
        ':recorrencia'   => $recorrencia,
        ':dia_vencimento'=> $dia_vencimento,
        ':data_fim'      => $data_fim
    ]);

    http_response_code(201);
    echo json_encode([
        "success"   => true,
        "message"   => "Conta fixa cadastrada com sucesso",
        "data"      => [
            "id"            => $pdo->lastInsertId(),
            "nome"          => $nome,
            "descricao"     => $descricao,
            "categoria"     => $categoria,
            "valor"         => $valor,
            "recorrencia"   => $recorrencia,
            "dia_vencimento"=> $dia_vencimento,
            "data_fim"      => $data_fim
        ]
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao criar a conta fixa",
        "error"   => $e->getMessage()
    ]);
}
