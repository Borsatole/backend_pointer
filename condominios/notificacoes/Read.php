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

$tabelabd = 'notificacoes';

global $pdo;

try {
    // -------------------------------
    // 1. Definição de filtros
    // -------------------------------
    $filtrosPermitidos = ['id_condominio', 'data_minima', 'data_maxima',];
    $filtros = [];
    $parametros = [];

    foreach ($filtrosPermitidos as $campo) {
    if (isset($_GET[$campo]) && $_GET[$campo] !== '') {
        $valor = trim($_GET[$campo]);

        if (in_array($campo, ['id_condominio', 'quantidade'])) {
            if (!is_numeric($valor)) {
                http_response_code(400);
                echo json_encode([
                    "success" => false,
                    "message" => "Campo $campo deve ser numérico"
                ]);
                exit();
            }
            $filtros[] = $campo === 'quantidade'
                ? "$campo <= :$campo"
                : "$campo = :$campo";
            $parametros[$campo] = ['valor' => (int)$valor, 'tipo' => PDO::PARAM_INT];
        } else {
            $filtros[] = "$campo LIKE :$campo";
            $parametros[$campo] = ['valor' => "%$valor%", 'tipo' => PDO::PARAM_STR];
        }
    }
}


    $where = $filtros ? "WHERE " . implode(" AND ", $filtros) : "";

    // -------------------------------
    // 2. Buscar tudo
    // -------------------------------
    $sqlCondominios = "SELECT * FROM $tabelabd $where ORDER BY id ASC";
    $stmtCondominios = $pdo->prepare($sqlCondominios);

    foreach ($parametros as $campo => $dado) {
        $stmtCondominios->bindValue(":$campo", $dado['valor'], $dado['tipo']);
    }

    $stmtCondominios->execute();
    $condominios = $stmtCondominios->fetchAll(PDO::FETCH_ASSOC);

    if (!$condominios) {
        echo json_encode([
            "success" => true,
            "total_registros" => 0,
            "Registros" => []
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }


    // -------------------------------
    // 5. Retorno final
    // -------------------------------
    echo json_encode([
        "success" => true,
        "total_registros" => count($condominios),
        "Registros" => $condominios,
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
