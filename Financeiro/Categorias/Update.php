<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

header("Content-Type: application/json; charset=UTF-8");

// Permitir apenas PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

// Recebe os dados
$data = json_decode(file_get_contents("php://input"), true);

$id    = isset($data['id']) ? (int)$data['id'] : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID da categoria não informado"]);
    exit();
}

// Campos possíveis de atualizar
$campos = ['categoria', 'setor'];
$setters = [];
$params = [];

foreach ($campos as $campo) {
    if (isset($data[$campo])) {
        $setters[] = "$campo = :$campo";
        $params[":$campo"] = $data[$campo];
    }
}

// Nenhum campo enviado
if (empty($setters)) {
    echo json_encode(["success" => false, "message" => "Nenhum campo para atualizar"]);
    exit();
}

// Atualização de timestamp
$setters[] = "data_atualizacao = CURRENT_TIMESTAMP";

$sql = "UPDATE financeiro_categorias SET " . implode(", ", $setters) . " WHERE id = :id";
$params[':id'] = $id;

try {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Categoria atualizada com sucesso"]);
    } else {
        echo json_encode(["success" => false, "message" => "Nenhuma categoria encontrada com o ID informado"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao atualizar registro",
        "error"   => $e->getMessage()
    ]);
}

?>