<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

// Método permitido
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

global $pdo;

// Parâmetros de paginação e filtro
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 20;
$setor = $_GET['setor'] ?? null;

$offset = ($pagina - 1) * $limite;

try {
    // Construir WHERE clause para filtro de setor
    $whereClause = "";
    $params = [];
    
    if ($setor && $setor !== 'todos') {
        $whereClause = " WHERE setor = :setor";
        $params[':setor'] = $setor;
    }

    // Consulta total de registros (com filtro)
    $sqlTotal = "SELECT COUNT(*) FROM financeiro_categorias" . $whereClause;
    $stmtTotal = $pdo->prepare($sqlTotal);
    
    // Bind dos parâmetros para contagem
    foreach ($params as $key => $value) {
        $stmtTotal->bindValue($key, $value);
    }
    
    $stmtTotal->execute();
    $totalRegistros = (int) $stmtTotal->fetchColumn();
    $totalPaginas = ceil($totalRegistros / $limite);

    // Consulta paginada (com filtro)
    $sql = "SELECT * FROM financeiro_categorias" . $whereClause . " ORDER BY categoria DESC LIMIT :limite OFFSET :offset";

    $stmt = $pdo->prepare($sql);
    
    // Bind dos parâmetros do filtro
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    // Bind dos parâmetros de paginação
    $stmt->bindValue(':limite', $limite, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $stmt->execute();
    $Registros = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success"         => true,
        "pagina"          => $pagina,
        "limite"          => $limite,
        "setor_filtrado"  => $setor, // Para debug/info
        "total_registros" => $totalRegistros,
        "total_paginas"   => $totalPaginas,
        "Registros"       => $Registros
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao buscar categorias: " . $e->getMessage()
    ]);
}