<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';


if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

$tabelabd = 'visitas';

global $pdo;

try {
    // -------------------------------
    // 1. Definição de filtros
    // -------------------------------
    $filtrosPermitidos = ['id_condominio', 'data_minima', 'data_maxima'];
    $filtros = [];
    $parametros = [];

    foreach ($filtrosPermitidos as $campo) {
        if (isset($_GET[$campo]) && $_GET[$campo] !== '') {
            $valor = trim($_GET[$campo]);

            if ($campo === 'id_condominio') {
                if (!is_numeric($valor)) {
                    http_response_code(400);
                    echo json_encode([
                        "success" => false,
                        "message" => "Campo $campo deve ser numérico"
                    ]);
                    exit();
                }
                $filtros[] = "$campo = :$campo";
                $parametros[$campo] = ['valor' => (int)$valor, 'tipo' => PDO::PARAM_INT];
            } elseif ($campo === 'data_minima') {
                $filtros[] = "entrada >= :data_minima";
                $parametros['data_minima'] = ['valor' => $valor . ' 00:00:00', 'tipo' => PDO::PARAM_STR];
            } elseif ($campo === 'data_maxima') {
                $filtros[] = "entrada <= :data_maxima";
                $parametros['data_maxima'] = ['valor' => $valor . ' 23:59:59', 'tipo' => PDO::PARAM_STR];
            }
        }
    }

    $where = $filtros ? "WHERE " . implode(" AND ", $filtros) : "";

    // -------------------------------
    // 2. Buscar tudo
    // -------------------------------
    $sqlVisitas = "SELECT * FROM $tabelabd $where ORDER BY entrada ASC";
    $stmtVisitas = $pdo->prepare($sqlVisitas);

    foreach ($parametros as $campo => $dado) {
        $stmtVisitas->bindValue(":$campo", $dado['valor'], $dado['tipo']);
    }


    $stmtVisitas->execute();
    $visitas = $stmtVisitas->fetchAll(PDO::FETCH_ASSOC);

    // -------------------------------
    // 3. Retorno final
    // -------------------------------
    echo json_encode([
        "success" => true,
        "total_registros" => count($visitas),
        "Registros" => $visitas,
        "filtros_aplicados" => array_keys($parametros)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro na consulta ao banco de dados",
        "error_details" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "message" => "Erro interno do servidor",
        "error_details" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
