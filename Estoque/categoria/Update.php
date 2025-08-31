<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id   = isset($data['id']) ? (int)$data['id'] : 0;
$nome = trim($data['nome'] ?? '');

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
    $pdo->beginTransaction();

    // 1) Captura o nome antigo
    $stmtOld = $pdo->prepare("SELECT nome FROM estoquecategorias WHERE id = :id FOR UPDATE");
    $stmtOld->execute([':id' => $id]);
    $rowOld = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$rowOld) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Nenhuma categoria encontrada com o ID informado"]);
        exit();
    }

    $nomeAntigo = $rowOld['nome'];
    $nomeNovo   = $nome !== '' ? $nome : $nomeAntigo;

    // 2) Atualiza a categoria
    $sql = "UPDATE estoquecategorias SET nome = :nome WHERE id = :id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':nome' => $nomeNovo,
        ':id'   => $id
    ]);

    // 3) Atualiza todos os itens de estoque que tinham a categoria antiga
    $contasAtualizadas = 0;
    if ($nomeNovo !== $nomeAntigo) {
        $sqlEstoque = "UPDATE estoque SET categoria = :novo WHERE categoria = :antigo";
        $stmt2 = $pdo->prepare($sqlEstoque);
        $stmt2->execute([
            ':novo'   => $nomeNovo,
            ':antigo' => $nomeAntigo
        ]);
        $contasAtualizadas = $stmt2->rowCount();
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Categoria atualizada com sucesso",
        "detalhes" => [
            "categoria_antiga" => $nomeAntigo,
            "categoria_nova"   => $nomeNovo,
            "estoque_atualizado" => $contasAtualizadas
        ]
    ]);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao atualizar registro",
        "error"   => $e->getMessage()
    ]);
}
