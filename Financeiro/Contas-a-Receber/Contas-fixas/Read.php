<?php
require_once dirname(__DIR__, 3) . '/vendor/autoload.php';
require_once dirname(__DIR__, 3) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 3) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 3) . '/conexao.php';

// Método permitido
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

global $pdo;

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 20;
$offset = ($pagina - 1) * $limite;

try {
    // Consulta total de registros
    $sqlTotal = "SELECT COUNT(*) FROM financeiro_contas_fixas";
    $stmtTotal = $pdo->prepare($sqlTotal);
    $stmtTotal->execute();
    $totalRegistros = (int) $stmtTotal->fetchColumn();
    $totalPaginas   = ceil($totalRegistros / $limite);

    // Consulta paginada
    $sql = "SELECT * FROM financeiro_contas_fixas ORDER BY valor DESC LIMIT :limite OFFSET :offset";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $contas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"         => true,
        "pagina"          => $pagina,
        "limite"          => $limite,
        "total_registros" => $totalRegistros,
        "total_paginas"   => $totalPaginas,
        "Registros"       => $contas
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao buscar contas fixas: " . $e->getMessage()
    ]);
}
