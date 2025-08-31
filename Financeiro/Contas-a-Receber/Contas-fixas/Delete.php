<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 3) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 3) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 3) . '/conexao.php';

// Método permitido
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

// Pegando ID da query string
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Validação obrigatória
if ($id <= 0) {
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "message" => "ID da categoria é obrigatório e deve ser um número válido"
    ]);
    exit();
}

try {
    global $pdo;
    $sql = "DELETE FROM financeiro_contas_fixas WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        http_response_code(200);
        echo json_encode([
            "success" => true,
            "message" => "Conta deletada com sucesso"
        ]);
    } else {
        http_response_code(404);
        echo json_encode([
            "success" => false,
            "message" => "Conta não encontrada"
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao deletar a categoria",
        "error" => $e->getMessage()
    ]);
}
