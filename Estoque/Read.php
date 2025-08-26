<?php
require_once dirname(__DIR__, 1) . '/vendor/autoload.php';
require_once dirname(__DIR__, 1) . '/middlewares/Autenticacao.php';
require_once(dirname(__DIR__, 1) . '/middlewares/lentidao-teste-api.php');
require_once(dirname(__DIR__, 1) . '/conexao.php');


// Verificação do método
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "message" => "Método não permitido"]);
    exit();
}

global $pdo;

try {
    // Parâmetros de paginação com validação
    $pagina = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
    $limite = isset($_GET['limite']) ? min(100, max(1, (int) $_GET['limite'])) : 20;
    $offset = ($pagina - 1) * $limite;

    // Campos de filtro permitidos
    $filtrosPermitidos = ['id', 'nome', 'quantidade', 'categoria'];
    $filtros = [];
    $parametros = [];

    // Construção dinâmica dos filtros
    foreach ($filtrosPermitidos as $campo) {
        if (isset($_GET[$campo]) && $_GET[$campo] !== '' && $_GET[$campo] !== null) {
            $valor = trim($_GET[$campo]);
            
            if ($valor === '') continue; // Skip valores vazios após trim
            
            if ($campo === 'quantidade' || $campo === 'id') {
                // Validação para campos numéricos
                if (!is_numeric($valor)) {
                    http_response_code(400);
                    echo json_encode([
                        "success" => false, 
                        "message" => "Valor inválido para o campo $campo. Deve ser numérico."
                    ]);
                    exit();
                }
                
                if ($campo === 'quantidade') {
                    // Quantidade -> busca IGUAL ou MENOR
                    $filtros[] = "$campo <= :$campo";
                } else {
                    // Id -> busca exata
                    $filtros[] = "$campo = :$campo";
                }
                
                $parametros[$campo] = [
                    'valor' => (int) $valor,
                    'tipo' => PDO::PARAM_INT
                ];
            } else {
                // Outros campos -> busca parcial com LIKE
                $filtros[] = "$campo LIKE :$campo";
                $parametros[$campo] = [
                    'valor' => '%' . $valor . '%',
                    'tipo' => PDO::PARAM_STR
                ];
            }
        }
    }

    // Monta cláusula WHERE
    $where = $filtros ? "WHERE " . implode(" AND ", $filtros) : "";

    // Consulta total de registros
    $sqlTotal = "SELECT COUNT(*) FROM estoque $where";
    $consultaTotal = $pdo->prepare($sqlTotal);
    
    // Bind dos parâmetros para contagem
    foreach ($parametros as $campo => $dado) {
        $consultaTotal->bindValue(":$campo", $dado['valor'], $dado['tipo']);
    }
    
    $consultaTotal->execute();
    $totalRegistros = (int) $consultaTotal->fetchColumn();
    $totalPaginas = $totalRegistros > 0 ? ceil($totalRegistros / $limite) : 0;

    // Se não há registros, retorna resultado vazio mas estruturado
    if ($totalRegistros === 0) {
        echo json_encode([
            "success" => true,
            "pagina" => $pagina,
            "limite" => $limite,
            "total_registros" => 0,
            "total_paginas" => 0,
            "Registros" => []
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }

    // Consulta paginada
    $sql = "SELECT * FROM estoque $where ORDER BY id DESC LIMIT :limite OFFSET :offset";
    $consulta = $pdo->prepare($sql);
    
    // Bind dos parâmetros de filtro
    foreach ($parametros as $campo => $dado) {
        $consulta->bindValue(":$campo", $dado['valor'], $dado['tipo']);
    }
    
    // Bind dos parâmetros de paginação
    $consulta->bindValue(':limite', $limite, PDO::PARAM_INT);
    $consulta->bindValue(':offset', $offset, PDO::PARAM_INT);
    
    $consulta->execute();
    $Registros = $consulta->fetchAll(PDO::FETCH_ASSOC);

    // Retorno JSON estruturado
    echo json_encode([
        "success" => true,
        "pagina" => $pagina,
        "limite" => $limite,
        "total_registros" => $totalRegistros,
        "total_paginas" => $totalPaginas,
        "Registros" => $Registros,
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
?>