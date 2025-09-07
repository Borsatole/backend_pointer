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

$tabelabd = 'visitas';

$data = json_decode(file_get_contents("php://input"), true);

$id_condominio = intval($data['id_condominio'] ?? 0);
$entrada = $data['entrada'] ?? '';
$saida = $data['saida'] ?? null;


if (!$id_condominio) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obrigatórios faltando"]);
    exit();
}


try {
    global $pdo;

    $pdo->exec("
    CREATE TABLE IF NOT EXISTS $tabelabd (
        id INT AUTO_INCREMENT PRIMARY KEY,
        id_condominio INT NOT NULL,
        entrada DATETIME NOT NULL,
        saida DATETIME NULL DEFAULT NULL,
        data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
        data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (id_condominio) REFERENCES condominios(id)
    )
");

    

    $sql = "INSERT INTO $tabelabd (id_condominio, entrada, saida) 
            VALUES (:id_condominio, :entrada, :saida)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':id_condominio', $id_condominio, PDO::PARAM_INT);
    $stmt->bindParam(':entrada', $entrada);
    $stmt->bindParam(':saida', $saida);
    $stmt->execute();

    $novoId = $pdo->lastInsertId();

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
