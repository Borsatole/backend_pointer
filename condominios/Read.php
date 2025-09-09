<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
require_once dirname(__DIR__, 1) . '/middlewares/Autenticacao-admin.php';
require_once dirname(__DIR__, 1) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 1) . '/conexao.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

global $pdo;

try {
    // -------------------------------
    // 1. Definição de filtros
    // -------------------------------
    $filtrosPermitidos = ['id', 'nome'];
    $filtros = [];
    $parametros = [];

    foreach ($filtrosPermitidos as $campo) {
        if (!empty($_GET[$campo])) {
            $valor = trim($_GET[$campo]);

            if (in_array($campo, ['id', 'quantidade'])) {
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
    // 2. Buscar todos os condomínios
    // -------------------------------
    $sqlCondominios = "SELECT * FROM condominios $where ORDER BY nome ASC";
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
    // 3. Preparar queries auxiliares
    // -------------------------------
    $sqlNotificacoes = "
    SELECT COUNT(*) as notificacoes_nao_lidas
    FROM notificacoes
    WHERE id_condominio = :id_condominio
      AND lida = 0
";
$stmtNotificacoes = $pdo->prepare($sqlNotificacoes);


    // // -------------------------------
    // // 4. Adicionar notificações e visitas em cada condomínio
    // // -------------------------------
    foreach ($condominios as $i => $condominio) {
    $stmtNotificacoes->bindValue(':id_condominio', $condominio['id'], PDO::PARAM_INT);
    $stmtNotificacoes->execute();
    $qtdNaoLidas = $stmtNotificacoes->fetchColumn(); // já pega só o número

    $condominios[$i]['notificacoes'] = (int)$qtdNaoLidas; // agora vira número
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
