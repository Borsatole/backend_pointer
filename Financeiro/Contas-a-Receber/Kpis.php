<?php
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/middlewares/Autenticacao.php';
require_once dirname(__DIR__, 2) . '/middlewares/lentidao-teste-api.php';
require_once dirname(__DIR__, 2) . '/conexao.php';

// ===============================
// Validação de método HTTP
// ===============================
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        "success" => false, 
        "message" => "Método não permitido. Use GET."
    ], JSON_UNESCAPED_UNICODE);
    exit();
}

// ===============================
// Classe para KPIs Financeiros
// ===============================
class FinanceiroKPIs {
    private PDO $pdo;
    
    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Formatar valor monetário
     */
    private function formatarMoeda(float $valor): string {
        return 'R$ ' . number_format($valor, 2, ',', '.');
    }
    
    /**
     * Validar e sanitizar datas
     */
    private function validarData(?string $data): ?string {
        if (!$data) return null;
        
        $timestamp = strtotime($data);
        if ($timestamp === false) {
            throw new InvalidArgumentException("Data inválida: $data");
        }
        
        return date('Y-m-d', $timestamp);
    }
    
    /**
     * Construir WHERE dinâmico para filtros de data
     */
    private function construirFiltroData(?string $dataMinima, ?string $dataMaxima): array {
        $where = [];
        $params = [];
        
        if ($dataMinima) {
            $where[] = "data_vencimento >= :data_minima";
            $params[':data_minima'] = $dataMinima;
        }
        
        if ($dataMaxima) {
            $where[] = "data_vencimento <= :data_maxima";
            $params[':data_maxima'] = $dataMaxima;
        }
        
        return [
            'clausula' => $where ? "WHERE " . implode(" AND ", $where) : "",
            'parametros' => $params,
            'condicoes' => $where
        ];
    }
    
    /**
     * Executar consulta de KPI
     */
    private function executarConsultaKPI(string $sql, array $params): array {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'quantidade' => (int)($resultado['quantidade'] ?? 0),
                'total' => (float)($resultado['total'] ?? 0)
            ];
        } catch (PDOException $e) {
            throw new RuntimeException("Erro na consulta KPI: " . $e->getMessage());
        }
    }
    
    /**
     * Buscar contas a pagar (todas)
     */
    private function buscarContasAPagar(string $filtroData, array $params): array {
        $sql = "SELECT COUNT(*) as quantidade, COALESCE(SUM(valor), 0) as total 
                FROM financeiro_contas_a_receber $filtroData";
        
        return $this->executarConsultaKPI($sql, $params);
    }
    
    /**
     * Buscar contas pagas
     */
    private function buscarContasPagas(array $condicoesData, array $params): array {
        $whereCompleto = array_merge(["data_pagamento IS NOT NULL"], $condicoesData);
        $filtro = "WHERE " . implode(" AND ", $whereCompleto);
        
        $sql = "SELECT COUNT(*) as quantidade, COALESCE(SUM(valor), 0) as total 
                FROM financeiro_contas_a_receber $filtro";
        
        return $this->executarConsultaKPI($sql, $params);
    }
    
    /**
     * Buscar contas pendentes (não pagas, ainda no prazo)
     */
    private function buscarContasPendentes(array $condicoesData, array $params): array {
        $whereCompleto = array_merge([
            "data_pagamento IS NULL", 
            "data_vencimento >= CURDATE()"
        ], $condicoesData);
        $filtro = "WHERE " . implode(" AND ", $whereCompleto);
        
        $sql = "SELECT COUNT(*) as quantidade, COALESCE(SUM(valor), 0) as total 
                FROM financeiro_contas_a_receber $filtro";
        
        return $this->executarConsultaKPI($sql, $params);
    }
    
    /**
     * Buscar contas atrasadas (não pagas, vencidas)
     */
    private function buscarContasAtrasadas(array $condicoesData, array $params): array {
        $whereCompleto = array_merge([
            "data_pagamento IS NULL", 
            "data_vencimento < CURDATE()"
        ], $condicoesData);
        $filtro = "WHERE " . implode(" AND ", $whereCompleto);
        
        $sql = "SELECT COUNT(*) as quantidade, COALESCE(SUM(valor), 0) as total 
                FROM financeiro_contas_a_receber $filtro";
        
        return $this->executarConsultaKPI($sql, $params);
    }
    
    /**
     * Calcular relação com período anterior
     */
    private function calcularRelacaoPeriodoAnterior(?string $dataMinima, ?string $dataMaxima): array {
        try {
            // Define período atual
            if (!$dataMinima || !$dataMaxima) {
                $dataMaxima = date('Y-m-d');
                $dataMinima = date('Y-m-d', strtotime('-30 days'));
            }
            
            $inicioAtual = new DateTime($dataMinima);
            $fimAtual = new DateTime($dataMaxima);
            $diasPeriodo = $inicioAtual->diff($fimAtual)->days + 1;
            
            // Define período anterior
            $fimAnterior = (clone $inicioAtual)->modify('-1 day');
            $inicioAnterior = (clone $fimAnterior)->modify("-" . ($diasPeriodo - 1) . " days");
            
            // Consulta otimizada para ambos períodos
            $sql = "SELECT 
                        SUM(CASE WHEN data_vencimento BETWEEN :inicio_atual AND :fim_atual THEN valor ELSE 0 END) as atual,
                        SUM(CASE WHEN data_vencimento BETWEEN :inicio_anterior AND :fim_anterior THEN valor ELSE 0 END) as anterior
                    FROM financeiro_contas_a_receber 
                    WHERE data_vencimento BETWEEN :inicio_anterior AND :fim_atual";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':inicio_atual' => $inicioAtual->format('Y-m-d'),
                ':fim_atual' => $fimAtual->format('Y-m-d'),
                ':inicio_anterior' => $inicioAnterior->format('Y-m-d'),
                ':fim_anterior' => $fimAnterior->format('Y-m-d')
            ]);
            
            $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
            $valorAtual = (float)($resultado['atual'] ?? 0);
            $valorAnterior = (float)($resultado['anterior'] ?? 0);
            
            // Calcula variação percentual
            if ($valorAnterior > 0) {
                $percentual = (($valorAtual - $valorAnterior) / $valorAnterior) * 100;
            } else {
                $percentual = $valorAtual > 0 ? 100 : 0;
            }
            
            return [
                "percentual" => round($percentual, 2),
                "positivo" => $percentual >= 0,
                "valor_atual" => $valorAtual,
                "valor_anterior" => $valorAnterior,
                "periodo_anterior" => [
                    "inicio" => $inicioAnterior->format('Y-m-d'),
                    "fim" => $fimAnterior->format('Y-m-d')
                ],
                "periodo_atual" => [
                    "inicio" => $inicioAtual->format('Y-m-d'),
                    "fim" => $fimAtual->format('Y-m-d')
                ]
            ];
            
        } catch (Exception $e) {
            // Log do erro e retorna valores padrão
            error_log("Erro no cálculo de período anterior: " . $e->getMessage());
            return [
                "percentual" => 0,
                "positivo" => true,
                "valor_atual" => 0,
                "valor_anterior" => 0,
                "erro" => "Não foi possível calcular a variação"
            ];
        }
    }
    
    /**
     * Gerar KPIs principais
     */
    public function gerarKPIs(?string $dataMinima = null, ?string $dataMaxima = null): array {
        // Validar datas
        $dataMinima = $this->validarData($dataMinima);
        $dataMaxima = $this->validarData($dataMaxima);
        
        // Construir filtros
        $filtroData = $this->construirFiltroData($dataMinima, $dataMaxima);
        
        // Buscar todos os KPIs
        $contasAPagar = $this->buscarContasAPagar($filtroData['clausula'], $filtroData['parametros']);
        $contasPagas = $this->buscarContasPagas($filtroData['condicoes'], $filtroData['parametros']);
        $contasPendentes = $this->buscarContasPendentes($filtroData['condicoes'], $filtroData['parametros']);
        $contasAtrasadas = $this->buscarContasAtrasadas($filtroData['condicoes'], $filtroData['parametros']);
        
        // Calcular relação com período anterior
        $relacaoPeriodo = $this->calcularRelacaoPeriodoAnterior($dataMinima, $dataMaxima);
        
        // Calcular percentuais
        $totalContas = $contasAPagar['quantidade'];
        $calcularPercentual = function(int $quantidade) use ($totalContas): float {
            return $totalContas > 0 ? round(($quantidade / $totalContas) * 100, 2) : 0;
        };
        
        return [
            "pagamentos" => [
                "titulo" => "Pagamentos Selecionados",
                "quantidade" => $contasAPagar['quantidade'],
                "descricao" => "{$contasAPagar['quantidade']} contas no período selecionado",
                "valor_total" => $contasAPagar['total'],
                "valor_formatado" => $this->formatarMoeda($contasAPagar['total']),
                "variacao" => [
                    "percentual" => $relacaoPeriodo['percentual'] . "%",
                    "positivo" => $relacaoPeriodo['positivo'],
                    "valor_anterior"  => $relacaoPeriodo['valor_anterior'],
                    "valor_anterior_formatado" => $this->formatarMoeda($relacaoPeriodo['valor_anterior'])
                ],
                "cor" => "orange"
            ],
            "pagamentos_recebidos" => [
                "titulo" => "Pagamentos Recebidos",
                "quantidade" => $contasPagas['quantidade'],
                "descricao" => "{$contasPagas['quantidade']} contas pagas no período selecionado",
                "valor_total" => $contasPagas['total'],
                "valor_formatado" => $this->formatarMoeda($contasPagas['total']),
                "variacao" => [
                    "percentual" => $calcularPercentual($contasPagas['quantidade']) . "%",
                    "positivo" => true,
                ],
                "cor" => "green"
            ],
            "pagamentos_pendentes" => [
                "titulo" => "Pagamentos Pendentes",
                "quantidade" => $contasPendentes['quantidade'],
                "descricao" => "{$contasPendentes['quantidade']} contas pendentes no período selecionado",
                "valor_total" => $contasPendentes['total'],
                "valor_formatado" => $this->formatarMoeda($contasPendentes['total']),
                "variacao" => [
                    "percentual" => $calcularPercentual($contasPendentes['quantidade']) . "%",
                    "positivo" => true,
                ],
                "cor" => "blue"
            ],
            "pagamentos_atrasados" => [
                "titulo" => "Pagamentos Atrasadas",
                "quantidade" => $contasAtrasadas['quantidade'],
                "descricao" => "{$contasAtrasadas['quantidade']} contas atrasadas no período selecionado",
                "valor_total" => $contasAtrasadas['total'],
                "valor_formatado" => $this->formatarMoeda($contasAtrasadas['total']),
                "variacao" => [
                    "percentual" => $calcularPercentual($contasAtrasadas['quantidade']) . "%",
                    "positivo" => false,
                ],
                "cor" => "red"
            ]
        ];
    }
}

// ===============================
// Execução principal
// ===============================
try {
    // Verificar conexão PDO
    if (!isset($pdo) || !($pdo instanceof PDO)) {
        throw new RuntimeException("Conexão com banco de dados não disponível");
    }
    
    // Capturar e validar parâmetros
    $dataMinima = $_GET['data_minima'] ?? null;
    $dataMaxima = $_GET['data_maxima'] ?? null;
    
    // Log de auditoria (opcional)
    error_log("KPIs solicitados - Período: {$dataMinima} a {$dataMaxima} - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    
    // Instanciar classe e gerar KPIs
    $kpisService = new FinanceiroKPIs($pdo);
    $registros = $kpisService->gerarKPIs($dataMinima, $dataMaxima);
    
    // Resposta de sucesso
    http_response_code(200);
    echo json_encode([
        "success" => true,
        "timestamp" => date('c'), // ISO 8601
        "periodo" => [
            "data_minima" => $dataMinima,
            "data_maxima" => $dataMaxima
        ],
        "registros" => $registros
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (InvalidArgumentException $e) {
    // Erro de validação de entrada
    http_response_code(400);
    echo json_encode([
        "success" => false,
        "error" => "Parâmetros inválidos",
        "message" => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
} catch (RuntimeException $e) {
    // Erro de sistema/banco
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Erro interno do servidor",
        "message" => "Erro ao processar KPIs financeiros"
    ], JSON_UNESCAPED_UNICODE);
    
    // Log detalhado do erro (não expor ao cliente)
    error_log("Erro KPIs: " . $e->getMessage() . " - " . $e->getTraceAsString());
    
} catch (Exception $e) {
    // Erro genérico
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => "Erro inesperado",
        "message" => "Falha no processamento da solicitação"
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Erro não tratado em KPIs: " . $e->getMessage());
}