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

// Parâmetros de paginação
$pagina = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$limite = isset($_GET['limite']) ? (int) $_GET['limite'] : 20;
$offset = ($pagina - 1) * $limite;

// Filtros permitidos
$filtrosPermitidos = ['id', 'categoria', 'data_minima', 'data_maxima', 'status'];
$filtros = [];
$parametros = [];

try {
    foreach ($filtrosPermitidos as $campo) {
        if (isset($_GET[$campo]) && $_GET[$campo] !== '') {

            if ($campo === 'id' || $campo === 'quantidade') {
                // Campos numéricos
                $filtros[] = "$campo = :$campo";
                $parametros[$campo] = [
                    'valor' => (int) $_GET[$campo],
                    'tipo'  => PDO::PARAM_INT
                ];
            } elseif ($campo === 'data_minima') {
                // Filtro por data mínima
                $filtros[] = "data_vencimento >= :data_minima";
                $parametros['data_minima'] = [
                    'valor' => $_GET['data_minima'],
                    'tipo'  => PDO::PARAM_STR
                ];
            } elseif ($campo === 'data_maxima') {
                // Filtro por data máxima
                $filtros[] = "data_vencimento <= :data_maxima";
                $parametros['data_maxima'] = [
                    'valor' => $_GET['data_maxima'],
                    'tipo'  => PDO::PARAM_STR
                ];
            } elseif ($campo === 'status') {
                // Filtro por status
                if ($_GET['status'] === 'pago') {
                    $filtros[] = "data_pagamento IS NOT NULL";
                } elseif ($_GET['status'] === 'pendente') {
                    $filtros[] = "data_pagamento IS NULL";
                }
            } else {
                $filtros[] = "$campo LIKE :$campo";
                $parametros[$campo] = [
                    'valor' => '%' . $_GET[$campo] . '%',
                    'tipo'  => PDO::PARAM_STR
                ];
            }
        }
    }

    // Monta cláusula WHERE
    $where = $filtros ? "WHERE " . implode(" AND ", $filtros) : "";

    // Consulta total de registros
    $sqlTotal = "SELECT COUNT(*) FROM financeiro_contas_a_pagar $where";
    $stmtTotal = $pdo->prepare($sqlTotal);
    foreach ($parametros as $campo => $dado) {
        $stmtTotal->bindValue(":$campo", $dado['valor'], $dado['tipo']);
    }
    $stmtTotal->execute();
    $totalRegistros = (int) $stmtTotal->fetchColumn();
    $totalPaginas   = ceil($totalRegistros / $limite);

    // Consulta paginada
    $sql = "SELECT * FROM financeiro_contas_a_pagar $where ORDER BY valor DESC LIMIT :limite OFFSET :offset";
    $consulta = $pdo->prepare($sql);
    foreach ($parametros as $campo => $dado) {
        $consulta->bindValue(":$campo", $dado['valor'], $dado['tipo']);
    }
    $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
    $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
    $consulta->execute();

    $Registros = $consulta->fetchAll(PDO::FETCH_ASSOC);
    

    echo json_encode([
        "success"         => true,
        "pagina"          => $pagina,
        "limite"          => $limite,
        "total_registros" => $totalRegistros,
        "total_paginas"   => $totalPaginas,
        "Registros"       => $Registros
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro ao buscar: " . $e->getMessage()
    ]);
}
