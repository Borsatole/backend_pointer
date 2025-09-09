<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
require_once dirname(__DIR__, 1) . '/middlewares/Autenticacao-admin.php';
require_once dirname(__DIR__, 1) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 1) . '/conexao.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

$tabelabd = 'condominios';

$data = json_decode(file_get_contents("php://input"), true);

$nome = trim($data['nome'] ?? '');
$telefone = trim($data['telefone'] ?? '');
$rua = trim($data['rua'] ?? '');

if (!$nome || !$telefone || !$rua) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obrigatórios faltando"]);
    exit();
}

try {
    global $pdo;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS condominios (
            id INT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            telefone VARCHAR(255) NOT NULL,
            rua VARCHAR(255) NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Busca o maior ID atual
    $result = $pdo->query("SELECT MAX(id) AS max_id FROM condominios");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $novoId = ($row['max_id'] ?? 0) + 1;

    $sql = "INSERT INTO condominios (id, nome, telefone, rua) 
            VALUES (:id, :nome, :telefone, :rua)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $novoId, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':telefone', $telefone);
    $stmt->bindParam(':rua', $rua);
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

