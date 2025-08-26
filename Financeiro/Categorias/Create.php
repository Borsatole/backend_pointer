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

$categoria = trim($data['categoria'] ?? '');
$setor     = trim($data['setor'] ?? '');

// Setores válidos
$setoresValidos = ['contas_a_pagar', 'contas_a_receber'];

// Validação dos campos
if (!$categoria || !$setor) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Campos obrigatórios faltando"
    ]);
    exit();
}

if (!in_array($setor, $setoresValidos)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "Setor inválido. Use 'contas_a_pagar' ou 'contas_a_receber'."
    ]);
    exit();
}

try {
    global $pdo;

    // Criar tabela se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS financeiro_categorias (
            id INT AUTO_INCREMENT PRIMARY KEY,
            categoria VARCHAR(255) NOT NULL,
            setor ENUM('contas_a_pagar','contas_a_receber') NOT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");

    // Inserir categoria
    $sql = "INSERT INTO financeiro_categorias (categoria, setor) VALUES (:categoria, :setor)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':categoria', $categoria);
    $stmt->bindParam(':setor', $setor);
    $stmt->execute();

    $novoId = $pdo->lastInsertId();

    http_response_code(201);
    echo json_encode([
        "success" => true,
        "message" => "Categoria criada com sucesso",
        "categoria"=> $categoria,
        "setor"    => $setor
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao criar a categoria",
        "error"   => $e->getMessage()
    ]);
}
