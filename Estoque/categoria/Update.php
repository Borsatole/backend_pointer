<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';


// Método permitido
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

// Recebe os dados
$data = json_decode(file_get_contents("php://input"), true);

// Validação simples
$id = isset($data['id']) ? (int)$data['id'] : 0;
$nome = trim($data['nome'] ?? '');



// Validação obrigatória
if (empty($id)) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "ID da categoria não informado"

    ]);
    exit();
}

try {
    global $pdo;
    $sql = "UPDATE estoquecategorias SET 
            nome = :nome
        WHERE id = :id";

    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':nome', $nome);

    $stmt->bindParam(':id', $id, PDO::PARAM_INT); // Faltava bind para o :id

    $stmt->execute();

    echo json_encode([
        "success" => true,
        "message" => "Atualizado com sucesso"
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao atualizar registro",
        "error" => $e->getMessage()
    ]);
}
