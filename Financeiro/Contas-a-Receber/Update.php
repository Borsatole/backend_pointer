<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

// Permitir apenas PUT
if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

// Recebe os dados
$data = json_decode(file_get_contents("php://input"), true);

$id = isset($data['id']) ? (int)$data['id'] : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID não informado"]);
    exit();
}

// Campos possíveis de atualizar
$campos = ['nome', 'descricao', 'categoria', 'valor', 'data_vencimento', 'data_pagamento'];
$setters = [];
$params = [];

foreach ($campos as $campo) {
    if (array_key_exists($campo, $data)) {
        if ($campo === 'data_pagamento' && $data[$campo] === "") {
            $setters[] = "$campo = NULL"; // insere NULL direto no SQL
        } else {
            $setters[] = "$campo = :$campo";
            $params[":$campo"] = $data[$campo];
        }
    }
}


// Se nenhum campo foi enviado
if (empty($setters)) {
    echo json_encode(["success" => false, "message" => "Nenhum campo para atualizar"]);
    exit();
}

// Adiciona atualização de timestamp
$setters[] = "data_atualizacao = CURRENT_TIMESTAMP";

$sql = "UPDATE financeiro_contas_a_receber SET " . implode(", ", $setters) . " WHERE id = :id";
$params[':id'] = $id;

try {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    if ($stmt->rowCount() > 0) {
        echo json_encode(["success" => true, "message" => "Atualizado com sucesso"]);
    } else {
        echo json_encode(["success" => false, "message" => "Nenhum registro alterado com o ID informado"]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Erro ao atualizar registro", "error" => $e->getMessage()]);
}

?>