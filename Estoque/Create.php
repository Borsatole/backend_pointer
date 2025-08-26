<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
require_once dirname(__DIR__, 1) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 1) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 1) . '/conexao.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$nome = trim($data['nome'] ?? '');
$quantidade = trim($data['quantidade'] ?? '');
$categoria = trim($data['categoria'] ?? '');

if (!$nome || !$quantidade || !$categoria) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obrigatórios faltando"]);
    exit();
}

try {
    global $pdo;

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS estoque (
            id INT PRIMARY KEY,
            nome VARCHAR(255) NOT NULL,
            quantidade INT NOT NULL,
            categoria VARCHAR(255) NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Busca o maior ID atual
    $result = $pdo->query("SELECT MAX(id) AS max_id FROM estoque");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    $novoId = ($row['max_id'] ?? 0) + 1;

    $sql = "INSERT INTO estoque (id, nome, quantidade, categoria) 
            VALUES (:id, :nome, :quantidade, :categoria)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $novoId, PDO::PARAM_INT);
    $stmt->bindParam(':nome', $nome);
    $stmt->bindParam(':quantidade', $quantidade, PDO::PARAM_INT);
    $stmt->bindParam(':categoria', $categoria);
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

