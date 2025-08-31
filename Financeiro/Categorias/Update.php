<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

header("Content-Type: application/json; charset=UTF-8");

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

$id = isset($data['id']) ? (int)$data['id'] : 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "ID da categoria não informado"]);
    exit();
}

// Monta SET dinâmico
$setters = [];
$params  = [':id' => $id];
if (array_key_exists('categoria', $data)) { $setters[] = "categoria = :categoria"; $params[':categoria'] = $data['categoria']; }
if (array_key_exists('setor', $data))     { $setters[] = "setor = :setor";         $params[':setor']     = $data['setor']; }

if (empty($setters)) {
    echo json_encode(["success" => false, "message" => "Nenhum campo para atualizar"]);
    exit();
}

$setters[] = "data_atualizacao = CURRENT_TIMESTAMP";

try {
    global $pdo;
    $pdo->beginTransaction();

    // 1) Captura o nome ANTIGO da categoria e bloqueia a linha
    $stmtOld = $pdo->prepare("SELECT categoria FROM financeiro_categorias WHERE id = :id FOR UPDATE");
    $stmtOld->execute([':id' => $id]);
    $rowOld = $stmtOld->fetch(PDO::FETCH_ASSOC);

    if (!$rowOld) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => "Nenhuma categoria encontrada com o ID informado"]);
        exit();
    }

    $categoriaAntiga = $rowOld['categoria'];
    $categoriaNova   = array_key_exists('categoria', $data) ? $data['categoria'] : $categoriaAntiga;

    // 2) Atualiza a tabela de categorias
    $sqlCategoria = "UPDATE financeiro_categorias SET " . implode(", ", $setters) . " WHERE id = :id";
    $stmtUp = $pdo->prepare($sqlCategoria);
    $stmtUp->execute($params);

    // 3) Se o nome mudou, propaga para contas_a_pagar
    $contasAtualizadas = 0;
    if ($categoriaNova !== $categoriaAntiga) {
        $sqlContas = "UPDATE financeiro_contas_a_pagar
                      SET categoria = :nova
                      WHERE categoria = :antiga";
        $stmtContas = $pdo->prepare($sqlContas);
        $stmtContas->execute([
            ':nova'   => $categoriaNova,
            ':antiga' => $categoriaAntiga
        ]);
        $contasAtualizadas = $stmtContas->rowCount();
    }

    $pdo->commit();

    echo json_encode([
        "success" => true,
        "message" => "Categoria atualizada com sucesso",
        "detalhes" => [
            "categoria_antiga" => $categoriaAntiga,
            "categoria_nova"   => $categoriaNova,
            "contas_atualizadas" => $contasAtualizadas
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
