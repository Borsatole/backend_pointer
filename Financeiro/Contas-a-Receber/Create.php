<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

header("Content-Type: application/json; charset=UTF-8");

// Permitir apenas POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        "success" => false,
        "message" => "Método não permitido. Use POST."
    ]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$nome = trim($data['nome'] ?? '');
$descricao = trim($data['descricao'] ?? '');
$categoria = trim($data['categoria'] ?? '');
$valor = trim($data['valor'] ?? '');
$data_vencimento = trim($data['data_vencimento'] ?? '');
$data_pagamento = trim($data['data_pagamento'] ?? '');




// Setores válidos
$setoresValidos = ['contas_a_pagar', 'contas_a_receber'];

// Validação dos campos
if (!$categoria ) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Campos obrigatórios faltando"
    ]);
    exit();
}


try {
    global $pdo;

    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS financeiro_contas_a_receber (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            descricao VARCHAR(255) NOT NULL,
            categoria VARCHAR(255) NOT NULL,
            valor DECIMAL(10,2) NOT NULL,
            data_vencimento DATE NOT NULL,
            data_pagamento DATE,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Inserir categoria
    $sql = "INSERT INTO financeiro_contas_a_receber (nome, descricao, categoria, valor, data_vencimento, data_pagamento) VALUES (:nome, :descricao, :categoria, :valor, :data_vencimento, :data_pagamento)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':descricao', $descricao);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':valor', $valor);
    $stmt->bindParam(':data_vencimento', $data_vencimento);
    $stmt->bindParam(':data_pagamento', $data_pagamento);
    $nome = $data['nome'] ?? '';
    $descricao = $data['descricao'] ?? '';
    $categoria = $data['categoria'] ?? '';
    $valor = $data['valor'] ?? '';
    $data_vencimento = $data['data_vencimento'] ?? '';
    $data_pagamento = !empty($data['data_pagamento']) ? $data['data_pagamento'] : null;




    $stmt->execute();

    $novoId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Conta criada com sucesso",

        "categoria"=> $categoria,
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao criar a conta",
        "error"   => $e->getMessage()
    ]);
}
