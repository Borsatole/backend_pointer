<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$categoria = trim($data['nome'] ?? '');

if (!$categoria) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obrigatórios faltando"]);
    exit();
}

try {
    global $pdo;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estoquecategorias (
            id INT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Busca o maior ID atual
    $result = $pdo->query("SELECT MAX(id) AS max_id FROM estoquecategorias");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $novoId = ($row['max_id'] ?? 0) + 1;

    $sql = "INSERT INTO estoquecategorias (id, nome, data_criacao, data_atualizacao)
            VALUES (:id, :nome, :data_criacao, :data_atualizacao)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $novoId, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $categoria);
    $stmt->bindParam(':data_criacao', $dataCriacao);
    $stmt->bindParam(':data_atualizacao', $dataAtualizacao);
    $dataCriacao = date('Y-m-d H:i:s');
    $dataAtualizacao = date('Y-m-d H:i:s');


    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => "Registro criado com sucesso",
        "novo_id" => $novoId
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao criar o registro",
        "error" => $e->getMessage()
    ]);
}

