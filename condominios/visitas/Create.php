<?php
date_default_timezone_set('America/Sao_Paulo');

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
$agora = date('Y-m-d H:i:s');

if (!$id_condominio) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Campos obrigatórios faltando"]);
    exit();
}

try {
    global $pdo;

    // Verifica se o condomínio existe
    $stmtCondominio = $pdo->prepare("SELECT id FROM condominios WHERE id = :id");
    $stmtCondominio->execute([':id' => $id_condominio]);
    $condominio = $stmtCondominio->fetch(PDO::FETCH_ASSOC);

    if (!$condominio) {
        http_response_code(200);
        echo json_encode(["success" => false, "message" => "Condomínio não encontrado"]);
        exit();
    }

    // Cria a tabela de visitas se não existir
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS $tabelabd (
            id INT AUTO_INCREMENT PRIMARY KEY,
            id_condominio INT NOT NULL,
            entrada DATETIME NULL DEFAULT NULL,
            saida DATETIME NULL DEFAULT NULL,
            data_criacao DATETIME DEFAULT CURRENT_TIMESTAMP,
            data_atualizacao DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (id_condominio) REFERENCES condominios(id)
        )
    ");

    // Pega último registro (entrada ou saída)
    $stmtUltima = $pdo->prepare("SELECT entrada, saida FROM $tabelabd WHERE id_condominio = :id_condominio ORDER BY id DESC LIMIT 1");
    $stmtUltima->execute([':id_condominio' => $id_condominio]);
    $ultimaVisita = $stmtUltima->fetch(PDO::FETCH_ASSOC);

    if ($ultimaVisita) {
        $ultimoHorario = $ultimaVisita['saida'] ?? $ultimaVisita['entrada'];
        if ($ultimoHorario) {
            $diff = strtotime($agora) - strtotime($ultimoHorario);
            if ($diff < 2) {
                // CORREÇÃO: Retorna uma resposta JSON válida em vez de exit() vazio
                http_response_code(200);
                echo json_encode([
                    "success" => true, 
                    "message" => "Registro muito próximo do anterior, ignorada",
                    "ignored" => true
                ]);
                exit();
            }
        }
    }

    // Busca visita aberta
    $stmt = $pdo->prepare("SELECT * FROM $tabelabd WHERE id_condominio = :id_condominio AND saida IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->execute([':id_condominio' => $id_condominio]);
    $visitaAberta = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($visitaAberta) {
        // Fecha a visita
        $stmtUpdate = $pdo->prepare("UPDATE $tabelabd SET saida = :saida WHERE id = :id");
        $stmtUpdate->execute([
            ':saida' => $agora,
            ':id' => $visitaAberta['id']
        ]);
        $message = "Visita encerrada com sucesso";
        $visitaId = $visitaAberta['id'];
    } else {
        // Cria nova visita
        $stmtInsert = $pdo->prepare("INSERT INTO $tabelabd (id_condominio, entrada) VALUES (:id_condominio, :entrada)");
        $stmtInsert->execute([
            ':id_condominio' => $id_condominio,
            ':entrada' => $agora
        ]);
        $message = "Nova visita iniciada com sucesso";
        $visitaId = $pdo->lastInsertId();
    }

    http_response_code(200);
    echo json_encode([
        "success" => true,
        "message" => $message,
        "visita_id" => $visitaId,
        "horario" => $agora
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao processar a visita",
        "error" => $e->getMessage()
    ]);
}